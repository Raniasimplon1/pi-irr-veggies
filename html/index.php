<!DOCTYPE html>
<html>
	<head>
		<title>Nathan's Irrigation Log</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
		<style type="text/css">
			* { font-family:monospace; }
			td { text-align:center; border-right:1px solid black; }
			th { border-top:1px solid black;border-bottom:1px solid black; }
		</style>
	</head>
	<body>
		<?php
		include_once(__DIR__."/../src/globals.php");
		?>
		<table>
			<tr>
				<th>Date</th>
				<th>Temperature</th>
				<th>Daily Water Need (in)</th>
				<th>Rainfall (in)</th>
				<th>Irrigation (in)</th>
				<th>Irrigation (sec)</th>
				<th>7-Day Water Deficit (in)</th>
			</tr>
		<?php
		
		// Irrigation Scheduler
		$raw = json_decode(file_get_contents(IRR_FILE), true);
		$data = array();
		foreach ($raw as $date=>$myData) {
			$myData['date'] = $date;
			$data[] = $myData;
		}
		$rows = 0;
		for ($i=count($data)-1;$i>=0;$i--) {
			if ($rows > 10) {
				?>
			<tr>
				<th>Date</th>
				<th>Temperature</th>
				<th>Daily Water Need (in)</th>
				<th>Rainfall (in)</th>
				<th>Irrigation (in)</th>
				<th>Irrigation (sec)</th>
				<th>7-Day Water Deficit (in)</th>
			</tr>
				<?php
				$rows = 0;
			}
			$rows++;
			$rowStyle = '';
			if (isset($data[$i]['irrt']) && $data[$i]['irrt'] > 0) { $rowStyle = ' style="background-color:blue;color:white;font-weight:bold;"'; }
			else if ($data[$i]['pcpt'] >= 5) { $rowStyle = ' style="background-color:green;color:white;"'; }

			$tmp = !is_null($data[$i]['tmpa']) ? sprintf("%0.1f", CelsiusToFahrenheit($data[$i]['tmpa'])) : 'N/A';
			$need = !is_null($data[$i]['need']) ? sprintf("%0.2f", mmToInches($data[$i]['need'])) : 'N/A';
			$rain = !is_null($data[$i]['pcpt']) ? sprintf("%0.2f", mmToInches($data[$i]['pcpt'])) : 'N/A';
			$irrTime = isset($data[$i]['irrTime']) ? $data[$i]['irrTime'] : 0;
			$irr = isset($data[$i]['irrt']) ? sprintf("%0.2f", mmToInches($data[$i]['irrt'])) : 'N/A';
			//$irr = sprintf("%0.2f", mmToInches($irrTime/IRRIGATION_1MM_DURATION));
			$deficit = isset($data[$i]['wdef']) ? sprintf("%0.2f", mmToInches($data[$i]['wdef'])) : 'N/A';
			echo "
			<tr{$rowStyle}>
				<td>".$data[$i]['date']."</td>
				<td>".$tmp."</td>
				<td>".$need."</td>
				<td>".$rain."</td>
				<td>".$irr."</td>
				<td>".$irrTime."</td>
				<td>".$deficit."</td>
			</tr>
			";
		}
		
		function CelsiusToFahrenheit($tempC) {
			if ($tempC === null) { return null; }
			return floatval($tempC)*(9/5)+32;
		}
		
		function mmToInches($mm) {
			if ($mm === null) { return null; }
			return floatval($mm)*0.0393701;
		}
		?>
		</table>
	</body>
</html>