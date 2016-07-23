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

# Data object defines
schedulePickleFilename = "schedule.pickle"
stateMachinePickleFilename = "state.pickle"

try:
	print "Unpickling schedule pickle"
	schedule = pickle.load(open(schedulePickleFilename, 'rb'))
except IOError:
	print "File not found, creating"
	# Make the file
	schedule = [{"DayOfWeek" : "Monday", "OnTime" : "10:00:00 AM", "OffTime" : "11:00:00 AM", "PreSoak" : True}]
	pickle.dump(schedule, open(schedulePickleFilename, 'wb'))
	schedule = pickle.load(open(schedulePickleFilename, 'rb'))
except:
	print("Error:", sys.exc_info()[0])

try:
	print "Unpickling state machine pickle"
	state = pickle.load(open(stateMachinePickleFilename, 'rb'))
except IOError:
	print "File not found, creating"
	#Make the file
	state = {"Current State" : "IDLE"}
	pickle.dump(state, open(stateMachinePickleFilename, 'wb'))
	state = pickle.load(open(stateMachinePickleFilename, 'rb'))
except:
	print("Error:", sys.exc_info()[0])

# Sate functions
def IdleState():
	print "Idle State"

def ErrorState():
	print "ERROR STATE"
	sys.exit(-1)

def PresoakState():
	print "Pre-Soak State"

def HighState():
	print "High fan state"

def LowState():
	print "Low fan state"

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


