#!/bin/python

import mysql.connector
from datetime import datetime, date, time, timedelta
import pytz
from tzlocal import get_localzone
localtz = get_localzone()

#schedule follows format:
#schedule = [{"DayOfWeek" : 0, "OnTime" : time(10, 00, 00, tzinfo=localtz), "OffTime" : time(13, 0, 0, tzinfo=localtz), "State": "HIGH"}]

class MySql:
	cnx = None

	def __init__(self):
		print "Initializing MySql object"
		self.cnx = mysql.connector.connect(user='CoxHome', password='1590N1500W', host='fowie.com', database='CoxHome')

	def __del__(self):
		print "Destorying MySql object"
		self.cnx.close()

	# return an array of dicts containing all of the schedules from the database
	def GetSchedules(self):
		print "GetSchedules called"
		cursor = self.cnx.cursor()
		schedules = []
		query = "SELECT * FROM Schedule ORDER BY OnTime"
		cursor.execute(query)
		for (ID, DayOfWeek, OnTime, OffTime, State) in cursor:
			schedules.append({"DayOfWeek":DayOfWeek, "OnTime" : OnTime, "OffTime" : OffTime, "State" : State})
		cursor.close()
		return schedules	
