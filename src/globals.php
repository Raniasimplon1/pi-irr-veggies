<?php
global $dbh;
define('IRR_FILE', __DIR__.'/../data/data.json');
define('CONFIG_FILE', __DIR__.'/../data/config.json');
define('UPLOAD_URL', 'https://SOMEADDRESS.COM/MYBACKUP');
define('UPLOAD_KEY', 'MySuperSecretAwesomeKey');

if (file_exists(CONFIG_FILE)) {
	$config = json_decode(file_get_contents(CONFIG_FILE), true);
	foreach ($config as $key=>$val) {
		define($key, $val);
	}
} else {
	trigger_error("COULD NOT FIND CONFIGURATION FILE!!! ".CONFIG_FILE);
}

// DEFAULT VALUES
// Source: https://bonnieplants.com/library/how-much-water-do-vegetables-need/
// My station records and reports in Metric so some conversions were needed
if (!defined('MIN_IRRIGATION')) { define('MIN_IRRIGATION', 12); }
if (!defined('BASE_WEEKLY_WATER')) { define('BASE_WEEKLY_WATER', 25); }// millimeters
if (!defined('BASE_TEMP')) { define('BASE_TEMP', 15); }// celcius
if (!defined('BASE_TEMP_RANGE')) { define('BASE_TEMP_RANGE', 5); }// difference in celcius, very crude translation from F to C
if (!defined('BASE_TEMP_INCREASE')) { define('BASE_TEMP_INCREASE', 13); }// millimeters added per range


if (!defined('BASE_TEMP_INCREASE_PER_DEGREE')) { define('BASE_TEMP_INCREASE_PER_DEGREE', BASE_TEMP_INCREASE/BASE_TEMP_RANGE/7); }
if (!defined('BASE_DAILY_WATER')) { define('BASE_DAILY_WATER', BASE_WEEKLY_WATER/7); }
if (!defined('BASE_DAILY_WATER_INCREASE')) { define('BASE_DAILY_WATER_INCREASE', BASE_TEMP_INCREASE/BASE_TEMP_RANGE/7); }
?>
