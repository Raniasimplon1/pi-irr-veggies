<?php
include_once(__DIR__.'/../src/globals.php');
include_once(__DIR__.'/../src/Irrigation.php');
Irrigation::load();
Irrigation::getYesterday();
Irrigation::getForecast();
Irrigation::calculate();
Irrigation::commit();
Irrigation::apply();
Irrigation::commit();

/*
	You might want to enable remote backup support, this is where you would handle that. In my case I had a simple script on a remote server that I uploaded files to.
 */
if (defined('UPLOAD_URL')) {
	$ch = curl_init();

	$post = array(
		'data'=>file_get_contents(IRR_FILE),
		'uploadKey'=>UPLOAD_KEY
	);

	curl_setopt($ch, CURLOPT_URL,UPLOAD_URL);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$post);

	// in real life you should use something like:
	// curl_setopt($ch, CURLOPT_POSTFIELDS, 
	//          http_build_query(array('postvar1' => 'value1')));

	// receive server response ...
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$server_output = curl_exec ($ch);

	curl_close ($ch);
}
?>
