<?php
use CAO\Core\Filesystem;

if (!defined('CAO_API')) {
	die();
}

echo $GLOBALS['egw']->framework->header;
echo parse_navbar();
