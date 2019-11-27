#!/usr/bin/python3

import time
import json
import sys

from m365py import m365py
from m365py import m365message

macAddress = sys.argv[1]
m365action = sys.argv[2]
if sys.argv[3] == '1':
    autoReconnect = True
else:
    autoReconnect = False

# callback for received messages from scooter
def handle_message(m365_peripheral, m365_message, value):
    print (json.dumps(value, indent=4))


scooter = m365py.M365(macAddress, handle_message, autoReconnect)
scooter.connect()

if m365action == 'lightOn':
	scooter.request(m365message.turn_on_tail_light)
elif m365action == 'lightOff':
	scooter.request(m365message.turn_off_tail_light)
elif m365action == 'cruiseOn':
	scooter.request(m365message.turn_on_cruise)
elif m365action == 'cruiseOff':
  	scooter.request(m365message.turn_off_cruise)
elif m365action == 'lockOn':
    scooter.request(m365message.turn_on_lock)
elif m365action == 'lockOff':
    scooter.request(m365message.turn_off_lock)
time.sleep(5)

scooter.disconnect()
