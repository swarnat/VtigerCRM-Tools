<?php
/**
 * @param string $setting
 *
 * @return NULL|number
 */
function tools_return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g': // no break is ok!
            $val *= 1024;
        case 'm': // no break is ok!
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}

$memory_limit = tools_return_bytes(ini_get('memory_limit'));
if ($memory_limit < (256 * 1024 * 1024)) {
    @ini_set('memory_limit','256M');
}

//ini_set('display_errors', 1);
//error_reporting(E_ALL & ~E_NOTICE);

global $root_directory;
require_once(dirname(__FILE__)."/SwVtTools.php");
require_once(dirname(__FILE__)."/autoloader.php");

\SwVtTools\Autoload::register("SwVtTools", "~/modules/SwVtTools/lib");