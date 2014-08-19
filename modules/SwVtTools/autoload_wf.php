<?php
@ini_set('memory_limit','128M');

//ini_set('display_errors', 1);
//error_reporting(E_ALL & ~E_NOTICE);

global $root_directory;
require_once(dirname(__FILE__)."/SwVtTools.php");
require_once(dirname(__FILE__)."/autoloader.php");

\SwVtTools\Autoload::register("SwVtTools", "~/modules/SwVtTools/lib");