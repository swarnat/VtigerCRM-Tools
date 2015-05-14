<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Stefan Warnat <support@stefanwarnat.de>
 * Date: 15.10.14 10:32
 * You must not use this file without permission.
 */
namespace SwVtTools;

class DbCheck {
    public static function checkColumn($table, $colum, $type, $default = false, $resetType = false) {
        global $adb;

        if(!DbCheck::existTable($table)) {
            return false;
        }

        $result = $adb->query("SHOW COLUMNS FROM `".$table."` LIKE '".$colum."'");
        $exists = ($adb->num_rows($result))?true:false;

        if($exists == false) {
            if($type !== false) {
                echo "Add column '".$table."'.'".$colum."'<br>";
                $adb->query("ALTER TABLE `".$table."` ADD `".$colum."` ".$type." NOT NULL".($default !== false?" DEFAULT  '".$default."'":""), false);
                $exists = true;
            }
        } elseif($resetType == true) {
                $existingType = strtolower(html_entity_decode($adb->query_result($result, 0, 'type'), ENT_QUOTES));
                $existingType = str_replace(' ', '', $existingType);
                if($existingType != strtolower(str_replace(' ', '', $type))) {
                    $sql = "ALTER TABLE  `".$table."` CHANGE  `".$colum."`  `".$colum."` ".$type.";";
                    $adb->query($sql);
                }
        }

        return $exists;
    }
    public static function existTable($tableName) {
        global $adb;
        $tables = $adb->get_tables();

        foreach($tables as $table) {
            if($table == $tableName)
                return true;
        }

        return false;
    }

    public static function checkRepositoryDB() {
         $initRepository = false;
         $adb = \PearDatabase::getInstance();
    }
}