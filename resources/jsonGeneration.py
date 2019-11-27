#!/usr/bin/python3

import time
import json
import sys

from m365py import m365py
from m365py import m365message

#import logging
#logging.getLogger('m365py').setLevel(logging.DEBUG)

# parametres
macAddress = sys.argv[1]
if sys.argv[2]=='1':
    autoReconnect = True 
else:
    autoReconnect = False

# callback for received messages from scooter
def handle_message(m365_peripheral, m365_message, value):
    ret = 1

scooter = m365py.M365(macAddress, handle_message, autoReconnect)
scooter.connect()

# Request all currently supported 'attributes'
scooter.request(m365message.battery_voltage)
scooter.request(m365message.battery_ampere)
scooter.request(m365message.battery_percentage)
scooter.request(m365message.battery_cell_voltages)
scooter.request(m365message.battery_info)

scooter.request(m365message.general_info)
scooter.request(m365message.motor_info)
scooter.request(m365message.trip_info)
scooter.request(m365message.trip_distance)
scooter.request(m365message.distance_left)
scooter.request(m365message.speed)
scooter.request(m365message.supplementary)
scooter.request(m365message.tail_light_status)
scooter.request(m365message.lock_status)
scooter.request(m365message.cruise_status)

# generation du fichier json de donnees
print(json.dumps(scooter.cached_state, indent=4, sort_keys=True))

time.sleep(5)

scooter.disconnect()
