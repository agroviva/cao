<?php

$_GET['cd'] = 'no';
$GLOBALS['egw_info']['flags'] = [
		'currentapp'    => 'cao',
		'noheader'      => true,
		'nonavbar'      => true,
];
include '../header.inc.php';
$GLOBALS['egw']->redirect_link('/egroupware/cao/graph/verkauf/invoice.php');
