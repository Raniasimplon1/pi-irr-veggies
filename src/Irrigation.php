<?php
$galPerMin = (CUPS_PER_5MIN/5) * 0.0625;
$squareFeetPerHole = 0.25;// Area = 12in x 3in
define('IRRIGATION_1MM_DURATION', Irrigation::getIrrigationRate($galPerMin, $squareFeetPerHole));// seconds/mm

class Irrigation {
	public static $dataFile;
	public static $data;
	public static $today;

	public static function load() {
		self::$dataFile = __DIR__.'/../data/data.json';
		self::$data = file_exists(self::$dataFile) ? json_decode(file_get_contents(self::$dataFile), true) : array();
	}
	public static function commit() {
		ksort(self::$data);
		file_put_contents(
			self::$dataFile,
			json_encode(self::$data, JSON_NUMERIC_CHECK|JSON_PRETTY_PRINT)
		);
	}

	// I didn't feel like trying to convert this to use metric units
	public static function getIrrigationRate($galPerMin,$sqFtPerHole) {
		// 96.25 - A constant that converts gallons per minute (GPM) to inches per hour. It is derived from 60 minutes per hour divided by 7.48 gallons per cubic foot. times 12 inches per foot. http://www.sprinklerwarehouse.com/DIY-Calculating-Precipitation-Rate-s/7942.htm
		$inPerHour = (96.25 * $galPerMin) / $sqFtPerHole;
		$mmPerHour = 25.4 * $inPerHour;
		$mmPerSecond = ($mmPerHour / 60) / 60;
		$mmPerSecond = $inPerHour * 0.00705556;

		return 1/$mmPerSecond;
	}

	public static function apply() {
		$date = isset(self::$today) ? self::$today : date('Y-m-d');
		if (isset(self::$data[$date]) && self::$data[$date]['wdef'] > MIN_IRRIGATION) {
			$duration = self::$data[$date]['wdef'] * IRRIGATION_1MM_DURATION;
			$duration = (int)sprintf("%0.0f", $duration);
			error_log("Applying ".self::$data[$date]['wdef']."mm of water in $duration seconds");
			exec('python '.__DIR__.'/../scripts/run_irrigation.py '.($duration+IRRIGATION_SPINUP_SECONDS));
			exec('python '.__DIR__.'/../scripts/run_irrigation.py 0');// To make sure that it shut off properly.
			self::$data[$date]['irrTime'] = isset(self::$data[$date]['irrTime']) ? self::$data[$date]['irrTime'] + $duration : $duration;
			self::$data[$date]['irrt'] = (float)sprintf("%0.2f", self::$data[$date]['irrTime'] / IRRIGATION_1MM_DURATION);
		}
		self::calculate();
	}

	public static function calculate() {
		$rollingNeeds = array();
		$rollingPrecip = array();
		$rollingIrrigation = array();
		foreach (self::$data as $date=>$day) {
			$dailyNeed = BASE_DAILY_WATER;
			$tmp = $day['tmpa'];
			$tmp -= BASE_TEMP;
			if ($tmp > 0) {
				$dailyNeed += $tmp*BASE_TEMP_INCREASE_PER_DEGREE;
			}
			$dailyNeed = sprintf("%0.2f", $dailyNeed);
			self::$data[$date]['need'] = $dailyNeed;

			$rollingNeeds[] = $dailyNeed;
			$rollingPrecip[] = $day['pcpt'];
			$rollingIrrigation[] = isset($day['irrt']) ? $day['irrt'] : 0;

			self::$data[$date]['wdef'] = array_sum($rollingNeeds) - array_sum($rollingPrecip) - array_sum($rollingIrrigation);
			if (self::$data[$date]['wdef'] < 0) {
				self::$data[$date]['wdef'] = 0;
				// You can't have a negative deficit in this instance. We need to reset the counters.
				while (array_sum($rollingNeeds) - array_sum($rollingPrecip) - array_sum($rollingIrrigation) < 0) {
					array_shift($rollingNeeds);
					array_shift($rollingPrecip);
					array_shift($rollingIrrigation);
				}
			}

			ksort(self::$data[$date]);
		}
	}

	public static function getForecast() {
		$address = 'http://api.wunderground.com/api/'.WUNDERGROUND_API_KEY.'/forecast/q/'.WUNDERGROUND_API_LOCATION.'.json';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $address);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if( ! $result = curl_exec($ch)) {
			trigger_error(curl_error($ch));
			return false;
		} 
		curl_close($ch);

		$tmp = json_decode($result, true);
		if (is_array($tmp) && isset($tmp['forecast'])) {
			foreach ($tmp['forecast']['simpleforecast']['forecastday'] as $forecast) {
				$date = date("Y-m-d", $forecast['date']['epoch']);
				if (!isset(self::$data[$date])) {
					self::$data[$date] = array();
				}
				self::$data[$date]['type'] = 'fcast';
				self::$data[$date]['pcpt'] = $forecast['qpf_allday']['mm'];
				self::$data[$date]['rlha'] = $forecast['avehumidity'];
				self::$data[$date]['tmpa'] = ($forecast['high']['celsius'] + $forecast['low']['celsius']) / 2;
				self::$data[$date]['wspa'] = $forecast['avewind']['kph'];

				// Oh yay we get to calculate this...
				self::$data[$date]['dwpa'] = self::$data[$date]['tmpa'] - ((100 - self::$data[$date]['rlha'])/5);
				if (self::$data[$date]['dwpa'] >= self::$data[$date]['tmpa']) {
					self::$data[$date]['dwpa'] = self::$data[$date]['tmpa'] - 0.1;
				}

				ksort(self::$data[$date]);
			}
		} else if (is_array($tmp)) {
			trigger_error("NO FORECAST");
			return false;
		} else {
			trigger_error("COULDN'T PARSE RESPONSE");
			return false;
		}
	}

	public static function getYesterday() {
		$address = 'http://api.wunderground.com/api/'.WUNDERGROUND_API_KEY.'/yesterday/q/'.WUNDERGROUND_API_LOCATION.'.json';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $address);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if( ! $result = curl_exec($ch)) {
			trigger_error(curl_error($ch));
			return false;
		} 
		curl_close($ch);

		$tmp = json_decode($result, true);
		if (is_array($tmp) && isset($tmp['history'])) {
			$date = $tmp['history']['dailysummary'][0]['date']['year'].'-'
						.$tmp['history']['dailysummary'][0]['date']['mon'].'-'
						.$tmp['history']['dailysummary'][0]['date']['mday'];
			self::$today = date('Y-m-d', strtotime($date) + 24 * 60 * 60);
			if (!isset(self::$data[$date])) {
				self::$data[$date] = array();
			}
			self::$data[$date]['type'] = 'obs';
			self::$data[$date]['dwpa'] = $tmp['history']['dailysummary'][0]['meandewptm'];
			self::$data[$date]['pcpt'] = $tmp['history']['dailysummary'][0]['precipm'];
			self::$data[$date]['rlha'] = $tmp['history']['dailysummary'][0]['humidity'];
			self::$data[$date]['tmpa'] = $tmp['history']['dailysummary'][0]['meantempm'];
			self::$data[$date]['wspa'] = $tmp['history']['dailysummary'][0]['meanwindspdm'];

			$srst = 0;
			foreach ($tmp['history']['observations'] as $obs) {
				$srst += (float)$obs['solarradiation'];
			}
			if ($srst > 0) {
				self::$data[$date]['srst'] = $srst;
			}

			ksort(self::$data[$date]);
		} else if (is_array($tmp)) {
			trigger_error("NO HISTORY");
			return false;
		} else {
			trigger_error("COULDN'T PARSE RESPONSE");
			return false;
		}
	}
}

?>
