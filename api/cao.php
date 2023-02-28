<?php

use AgroEgw\Api\Enqueue;
use AgroEgw\DB;
use CAO\Core\Filesystem;
use EGroupware\Api\Asyncservice;
use EGroupware\Api\Header\ContentSecurityPolicy as CSP;

define('APPDIR', dirname(__DIR__));

define('TITLE', 'CAO Faktura');
define('CAO_API', true);
define('CAO_URL', 'http://cao.agroviva.net:9876/cao/import.php');
define('CAO_CREDENTIALS', base64_encode('agroviva:keineAhnung666'));
define('INSTANCE', explode('.', $_SERVER['SERVER_NAME'])[0]);

switch (INSTANCE) {
	case 'e00':
		define('DEBUG_MODE', false);
		break;

	default:
		define('DEBUG_MODE', false);
		break;
}
$_GET['cd'] = 'no';
$GLOBALS['egw_info']['flags'] = [
	'currentapp'    => 'cao',
	'noheader'      => true,
	'nonavbar'      => true,
];

require_once __DIR__.'/../../header.inc.php';
if (file_exists(__DIR__.'/../../agroviva/vendor/autoload.php')) {
	require_once __DIR__.'/../../agroviva/vendor/autoload.php';
} else {
	require_once __DIR__.'/../vendor/autoload.php';
}
require_once __DIR__.'/../inc/classes/autoload.php';
require_once __DIR__.'/../functions/autoload.php';

$async = new asyncservice();

if (($async->read('cao')['cao']['method'] != 'cao.cao_sync.synchron')) {
	$async->delete('cao');
	$async->set_timer(['hour' => '59*/2'], 'cao', 'cao.cao_sync.synchron', null);
}

$db = (new DB("SHOW TABLES LIKE 'egw_cao'"))->Fetch();
if (empty($db)) {
	$async->delete('cao');
	exit();
}

ob_get_clean();
$me = (new DB("SELECT * FROM egw_cao_meta WHERE meta_connection_id = '".$GLOBALS['egw_info']['user']['account_id']."'"))->Fetch();
define('PREMISSION', (is_array($me) && !empty($me) || $GLOBALS['egw_info']['user']['apps']['admin'] || ($GLOBALS['egw_info']['user']['account_id'] == '116')));
define('MA_ID', $me['meta_data']);

Enqueue::Script('/cao/js/lib/nprogress.js');
Enqueue::Script('/cao/js/Settings.js');

CSP::add_script_src(['self', 'unsafe-eval', 'unsafe-inline']);
CSP::add('font-src', ['fonts.gstatic.com']);
CSP::add('font-src', ['maxcdn.icons8.com']);
CSP::add('style-src', ['https://fonts.googleapis.com/']);
CSP::add('style-src', ['https://maxcdn.icons8.com/']);
CSP::add('script-src', ['https://cdn.datatables.net']);

Filesystem::start();
