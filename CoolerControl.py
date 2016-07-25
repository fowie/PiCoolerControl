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

import MySQL

# Data object defines
schedulePickleFilename = "schedule.pickle"
stateMachinePickleFilename = "state.pickle"
defaultPresoakTime = timedelta(minutes=5)
presoakDelayTime = timedelta(minutes=30)
lastPresoakTimeFilename = "presoak.pickle"

GPIOon = "0";
GPIOoff = "1";
GPIOwrite = "gpio -g write ";
GPIOread = "gpio -g read ";
PINS = {"Pump" : "2", "High" : "3", "Low" : "4"};

MySql = MySQL.MySql()
schedule = MySql.GetSchedules()
print "Schedules:"
for sch in schedule:
	print sch
#try:
#	print "Unpickling schedule pickle"
#	schedule = pickle.load(open(schedulePickleFilename, 'rb'))
#except IOError:
#	print "File not found, creating"
#	# Make the file
#	schedule = [{"DayOfWeek" : 0, "OnTime" : time(10, 00, 00, tzinfo=localtz), "OffTime" : time(13, 0, 0, tzinfo=localtz), "State": "HIGH"}]
#	pickle.dump(schedule, open(schedulePickleFilename, 'wb'))
#	schedule = pickle.load(open(schedulePickleFilename, 'rb'))
#except:
#	print("Error:", sys.exc_info()[0])

try:
	print "Unpickling state machine pickle"
	state = pickle.load(open(stateMachinePickleFilename, 'rb'))
except IOError:
	print "File not found, creating"
	#Make the file
	state = {"Current State" : "IDLE", "Next State" : "IDLE", "Current State End Time" : time(0, 0, 0, tzinfo=localtz), "Next State Duration" : time(0, 0, 0, tzinfo=localtz)}
	pickle.dump(state, open(stateMachinePickleFilename, 'wb'))
	state = pickle.load(open(stateMachinePickleFilename, 'rb'))
except:
	print("Error:", sys.exc_info()[0])

try:
	print "Unpickling last presoak time"
	lastPresoak = pickle.load(open(lastPresoakTimeFilename, 'rb'))
except IOError:
	print "File not found, creating and setting presoak time to 1 hour in the past"
	lastPresoak = datetime.now(localtz) - timedelta(hours=1)
	pickle.dump(lastPresoak, open(lastPresoakTimeFilename, 'wb'))
	lastPresoak = pickle.load(open(lastPresoakTimeFilename, 'rb'))

def GpioSet(pin, newVal):
	global GPIOwrite, GPIOon, GPIOoff, PINS;
	command = GPIOwrite+" "+PINS[pin]+" "+newVal;
	print("Command: "+command);
	#sys.exec(command);

# Sate functions
def IdleState():
	print "Idle State"
	# ITerate through all available schedules and see if we need to change states
	# get useful variable values
	now = datetime.now(localtz)
	print "Current date and time is "+str(now) + " and day of week is "+str(now.date().weekday())
	for sch in schedule:
		print "This schedule starts at "+str(sch["OnTime"])+" on weekday "+str(sch["DayOfWeek"])
		if int(sch["DayOfWeek"]) == int(now.date().weekday()):  #weekday() returns an int where Monday is 0 and Sunday is 6
			print "Day of week matches"
			# time objects in MySQL get converted to just TimeDelta objects in python,
			# now that I know I'm on the right day of the week, make them into full datetimes using
			# today at 0:0:0 and add the timedeltas
			OnTime = localtz.localize(datetime.combine(now.date(), time(0,0,0)) + sch["OnTime"])
			OffTime = localtz.localize(datetime.combine(now.date(), time(0,0,0)) + sch["OffTime"])
			print OnTime
			print now
			if OnTime < now:
				print "Starting schedule"
				lastPresoakTime = now - lastPresoak
				print "Last presoak was "+str(lastPresoakTime)+" ago"
				if (now - lastPresoak) > presoakDelayTime:
					print "Presoaking"
					state["Current State"] = "PRESOAK"
					state["Next State"] = sch["State"]
					state["Current State End Time"] = now + defaultPresoakTime
					state["Next State Duration"] = sch["OffTime"] - sch["OnTime"]
				else:
					print "Skipping presoak"
					state["Current State"] = sch["State"]
					state["Next State"] = "IDLE"
					state["Current State End Time"] = new + (sch["OffTime"] - sch["OnTime"])
					state["Next State Duration"] = time(0,0,0,tzinfo=localtz)
			else:
				print "Skipping schedule.  Wrong time of day"
		else:
			print "Skipping. Wrong day of week"


def ErrorState():
	print "ERROR STATE"
	sys.exit(-1)

#turn on the pump, keep fan off
def PresoakState():
	global PINS, GPIOon, GPIOoff
	print "Pre-Soak State"
	GpioSet(PINS["Pump"], GPIOon)
	GpioSet(PINS["High"], GPIOoff)
	GpioSet(PINS["Low"], GPIOoff)

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
print "Starting Main.  Current State = "+str(state["Current State"])
Run = StateMachine.get(state["Current State"]) #, default = ErrorState()) # pass in current state, if state doesn't exist, go to Error state
Run()

now = datetime.now(localtz)
if state["Current State End Time"] < now:
	print "Moving to next state"
	state["Current State"] = state["Next State"]

