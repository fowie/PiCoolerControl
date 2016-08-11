#/bin/python
# Script will be run as a cron job every minute
# Steps:
# 1 - Obtain current state (read GPIO pins)
# 2 - Read the schedule
# 2a - Get the current position of the state machine
# 3 - Determine if anything needs to happen on the schedule
# 3a - Perform operations
#    - Determine if the pump was on recently
#    - If true, skip pre-soak
#    - If false, do pre-soak before starting

import pickle
import sys
from datetime import datetime, date, time, timedelta
import pytz
from tzlocal import get_localzone
localtz = get_localzone()
import subprocess
import RPi.GPIO as GPIO
GPIO.setmode(GPIO.BCM)
GPIO.setwarnings(False)
import MySQL

# Data object defines
schedulePickleFilename = "schedule.pickle"
stateMachinePickleFilename = "state.pickle"
defaultPresoakTime = timedelta(minutes=5)
presoakDelayTime = timedelta(minutes=30)
lastPresoakTimeFilename = "presoak.pickle"

GPIOon = False;
GPIOoff = True;
GPIOwrite = "gpio -g write ";
GPIOread = "gpio -g read ";
PINS = {"Pump" : 2, "High" : 3, "Low" : 4};

MySql = MySQL.MySql()
schedule = MySql.GetSchedules()
print "STARTING.  DATE: "+str(datetime.now())

state = MySql.GetState()

print "State:"+str(state)

for key,value in PINS.items():
	GPIO.setup(value, GPIO.OUT)

def GpioSet(pin, newVal):
	GPIO.output(pin, newVal)	

# Sate functions
def IdleState():
	print "Idle State"
	GpioSet(PINS["Pump"], GPIOoff)
	GpioSet(PINS["High"], GPIOoff)
	GpioSet(PINS["Low"], GPIOoff)
	# ITerate through all available schedules and see if we need to change states
	# get useful variable values
	now = datetime.now()
	print "Current date and time is "+str(now) + " and day of week is "+str(now.date().isoweekday())
	for sch in schedule:
		if int(sch["DayOfWeek"]) == int(now.date().isoweekday()):  #weekday() returns an int where Monday is 0 and Sunday is 6
			print "This schedule starts at "+str(sch["OnTime"])+" on weekday "+str(sch["DayOfWeek"])+". Day of week matches"
			# time objects in MySQL get converted to just TimeDelta objects in python,
			# now that I know I'm on the right day of the week, make them into full datetimes using
			# today at 0:0:0 and add the timedeltas
			OnTime = datetime.combine(now.date(), time(0,0,0)) + sch["OnTime"]
			OffTime = datetime.combine(now.date(), time(0,0,0)) + sch["OffTime"]
			if OnTime < now and OffTime > now:
				print "Starting schedule"
				lastPresoakTime = now - state['Last Presoak Time']
				print "Last presoak was "+str(lastPresoakTime)+" ago"
				if lastPresoakTime > presoakDelayTime:
					print "Presoaking"
					state["Current State"] = "PRESOAK"
					state["Next State"] = sch["State"]
					state["Current State End Time"] = now + defaultPresoakTime
					state["Next State Duration"] = sch["OffTime"] - sch["OnTime"]
				else:
					print "Skipping presoak"
					state["Current State"] = sch["State"]
					state["Next State"] = "IDLE"
					state["Current State End Time"] = now + (sch["OffTime"] - sch["OnTime"])
					state["Next State Duration"] = time(0,0,0)
				break
#			else:
#				print "Skipping schedule.  Wrong time of day"
#		else:
#			print "Skipping. Wrong day of week"


def ErrorState():
	print "ERROR STATE"
	sys.exit(-1)

#turn on the pump, keep fan off
def PresoakState():
	global PINS, GPIOon, GPIOoff, state, localtz
	print "Pre-Soak State"
	GpioSet(PINS["Pump"], GPIOon)
	GpioSet(PINS["High"], GPIOoff)
	GpioSet(PINS["Low"], GPIOoff)
	state['Last Presoak Time'] = datetime.now()
	
def HighState():
	global PINS, GPIOon, GPIOoff
	print "High fan state"
	GpioSet(PINS["Pump"], GPIOoff)
	GpioSet(PINS["High"], GPIOon)
	GpioSet(PINS["Low"], GPIOoff)

def LowState():
	global PINS, GPIOon, GPIOoff
	print "Low fan state"
	GpioSet(PINS["Pump"], GPIOoff)
	GpioSet(PINS["High"], GPIOoff)
	GpioSet(PINS["Low"], GPIOon)

# State machine definition #
StateMachine = {
	"IDLE": IdleState,
	"PRESOAK": PresoakState,
	"HIGH":	HighState,
	"LOW":	LowState,
}

# MAIN #
#print "Starting Main.  Current State = "+str(state["Current State"])
Run = StateMachine.get(state["Current State"]) #, default = ErrorState()) # pass in current state, if state doesn't exist, go to Error state
Run()

now = datetime.now()
#print now
if state["Current State End Time"] < now:
	print "Moving to next state: "+state["Next State"]
	state["Current State"] = state["Next State"]
	state["Current State End Time"] = datetime.now() + state["Next State Duration"]
	state["Next State"] = "IDLE"
else:
	print "Staying in state "+state["Current State"]
	print "Next state change is at "+str(state["Current State End Time"])

# Update State #
#pickle.dump(state, open(stateMachinePickleFilename, 'wb'))
MySql.SaveState(state)

