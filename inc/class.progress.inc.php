<?php

// header("Content-Type: text/event-stream");
header('content-type: application/json; charset=UTF-8');
header('Cache-Control: no-cache');

$_GET['cd'] = 'no';

use EGroupware\Api\Cache;

class progress
{
	public $public_functions = [
		'init'		=> true,
	];

	public function __construct()
	{
	}

	public function init()
	{
		ob_get_clean();
		ob_get_contents();
		// while (true) {
		// Send it in a message
		// echo "data: " . json_encode(Cache::getSession('cao', 'job_progress')) . "\n\n";
		echo json_encode(Cache::getSession('cao', 'job_progress'));
		ob_flush();
		flush();
		exit;

		// sleep(2);
		// }
	}
}
