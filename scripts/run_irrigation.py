#!/usr/bin/python 
#Import the necessary libraries
import RPi.GPIO as GPIO
import time
import sys

GPIO.setmode(GPIO.BCM)
#Setup pin 18 as an output
GPIO.setmode(GPIO.BCM)
GPIO.setup(18, GPIO.OUT)
#This function turns the valve on and off in 10 sec. intervals. 
def valve_OnOff(Pin):
    #while True:
    GPIO.output(18, GPIO.HIGH)
    print("Applying Water") 
    time.sleep(float(sys.argv[1])) #waiting time in seconds
    GPIO.output(18, GPIO.LOW)
    print("Done applying water.")
valve_OnOff(18)
GPIO.cleanup()
