# pi-irr-veggies
Extremely simplistic irrigation scheduler for Raspberry Pi controlled setups using Weather Underground as a data provider.

INSTRUCTIONS:
1. Install to a Raspberry Pi that has PHP and PHP-CURL enabled. Optionally with APache2 setup to be able to quickly check in to ensure that everything is working correctly.
2. Modify config.json with your Wunderground API Key and a WUNDERGROUND_API_LOCATION. I have a personal station on wunderground so I utilize that specifically to make sure my data is "hyper-local".
3. Create a crontab entry for "php /var/www/scripts/getSchedule.php". I recommend scheduling it to run around dawn and again around sunset. This is to ensure that you capture any corrections based upon real data for the day and to cover for the event that your power goes out. I have mine set to run at 6AM and then again at 6PM.

CONFIG FILE DEFINITIONS:

	MIN_IRRIGATION - Minimum amount of irrigation to apply at a given time in millimeters
	BASE_WEEKLY_WATER - Base Weekly Water need in millimeters
	BASE_TEMP - Base Temperature for the weekly need in Celsius
	BASE_TEMP_RANGE - Increments to use to add additional irrigation needs in Celsius. This VERY crudely simulates evaporation.
	BASE_TEMP_INCREASE - How much additional water to apply for the BASE_TEMP_RANGE increment. This value is in millimeters
	IRRIGATION_SPINUP_SECONDS - How long in seconds it takes for your irrigation system to achieve full water pressure. I recommend timing how long it takes to start up.
	CUPS_PER_5MIN - How many cups of water were collected in a 5 minute period from a single hole in the PVC to calibrate the application rate.
	WUNDERGROUND_API_KEY - Your Weather Underground API key
	WUNDERGROUND_API_LOCATION - What location you want to pull data for.
		Example of Specific City:	CA/San_Francisco
		Example of Specific Station:	pws:KCATAHOE2

CALIBRATION:
To calibrate my system I used a measuring cup at the fatherst point in the irrigation piping system. I manually triggered the irrigation to run and collected water for 5 minutes. 

IMPORTANT NOTICES:

1. This does not confirm that water was actually applied. If your water isn't running or the valve can't open this will not know.
2. If the Pi crashes out for whatever reason during application, the valve will remain open with water being applied until you can reset it.
3. This is not designed to confirm to any laws that may exist so check your local, state, and national regulations before utilizing this. If you are found to be in violation I am in no way responsible.

DOCUMENTATION:

Default Values Taken ROughly from http://www.sprinklerwarehouse.com/DIY-Calculating-Precipitation-Rate-s/7942.htm

My Build: https://plus.google.com/u/0/collection/srqHQB

Hardware Build Design that I used: http://www.instructables.com/id/Raspberry-Pi-Irrigation-Controller/?ALLSTEPS

PVC Pipe design inspired by: https://digitalcommons.usu.edu/cgi/viewcontent.cgi?referer=https://www.google.com/&httpsredir=1&article=2054&context=extension_curall
	If you space your PVC holes more than 3 inches apart, you'll want to modify the top of the Irrigation.php file to ensure proper calibration.