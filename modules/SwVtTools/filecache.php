<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Stefan Warnat <support@stefanwarnat.de>
 * Date: 10.02.15 18:41
 * You must not use this file without permission.
 */

function swtools_filecache($files, $cacheFile) {
    $fh = fopen($cacheFile, 'w');
    fwrite($fh, '<?php '.PHP_EOL);

    foreach($files as $module) {

        $content = file_get_contents($module);

        $content = preg_replace('/\?>$/', '', $content);
        $content = preg_replace('/^\<\?php/', '', $content);
        $content = preg_replace('/^\<\?/', '', $content);
        //var_dump($content);exit();

        $modulename = basename(dirname(dirname($module)));
        if($modulename != 'vtlib') {
            $content = '/* FILE: ' . $module . '*/' . PHP_EOL . 'if(!class_exists("'.$modulename.'_Module_Model", false)) { ' . PHP_EOL . $content . PHP_EOL . '}'.PHP_EOL;
        } else {
            $content = '/* FILE: ' . $module . '*/' . PHP_EOL . '' . PHP_EOL . $content . PHP_EOL . ''.PHP_EOL;
        }
        fwrite($fh, $content);
    }

    fclose($fh);
}
