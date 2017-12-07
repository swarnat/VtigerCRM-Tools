<?php

/**
 * Created by PhpStorm.
 * User: Stefan
 * Date: 02.05.2016
 * Time: 14:40
 */
namespace SwVtTools;

class Config
{
    private static $_TableName = 'vtiger_tools_config';

    public static function get($key, $default = -1) {
        $adb = \PearDatabase::getInstance();

        $sql = 'SELECT value FROM '.self::$_TableName.' WHERE `key` = ?';
        $result = $adb->pquery($sql, array($key), true);

        if($adb->num_rows($result) == 0) {
            return $default;
        }

        $value = html_entity_decode($adb->query_result($result, 0, 'value'));
        return VtUtils::parseJSON($value);
    }

    public static function set($key, $value) {
        $adb = \PearDatabase::getInstance();

        $sql = 'REPLACE INTO '.self::$_TableName.' SET `key` = ?, `value` = ?';
        $result = $adb->pquery($sql, array($key, $value), true);
    }
}