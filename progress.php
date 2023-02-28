<?php

header('Content-Type: text/event-stream');
// header('content-type: application/json; charset=UTF-8');
header('Cache-Control: no-cache');

$_GET['cd'] = 'no';
$GLOBALS['egw_info']['flags'] = [
	'currentapp'    => 'cao',
	'noheader'      => true,
	'nonavbar'      => true,
];
include '../header.inc.php';

use EGroupware\Api\Cache;

ob_get_clean();
ob_get_contents();
// while (true) {
	// Send it in a message
	echo 'data: '.json_encode(Cache::getSession('cao', 'job_progress'))."\n\n";
	// echo json_encode(Cache::getSession('cao', 'job_progress'));
	ob_flush();
	flush();
	exit;

	// sleep(2);
// }
