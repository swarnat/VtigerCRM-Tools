<?php
namespace SwVtTools;

class CustomView {
    public static function export($viewid) {
        global $adb;
        $data = array();

        $sql = "SELECT * FROM vtiger_customview WHERE cvid = ?";
        $result = $adb->pquery($sql, array($viewid));

        $data["vtiger_customview"] = $adb->fetchByAssoc($result);

        $sql = "SELECT * FROM vtiger_cvadvfilter WHERE cvid = ?";
        $result = $adb->pquery($sql, array($viewid));

        while($row = $adb->fetchByAssoc($result)) {
            $data["vtiger_cvadvfilter"][] = $row;
        }

        $sql = "SELECT * FROM vtiger_cvadvfilter_grouping WHERE cvid = ?";
        $result = $adb->pquery($sql, array($viewid));

        while($row = $adb->fetchByAssoc($result)) {
            $data["vtiger_cvadvfilter_grouping"][] = $row;
        }

        $sql = "SELECT * FROM vtiger_cvcolumnlist WHERE cvid = ?";
        $result = $adb->pquery($sql, array($viewid));

        while($row = $adb->fetchByAssoc($result)) {
            $data["vtiger_cvcolumnlist"][] = $row;
        }

        $sql = "SELECT * FROM vtiger_cvstdfilter WHERE cvid = ?";
        $result = $adb->pquery($sql, array($viewid));

        while($row = $adb->fetchByAssoc($result)) {
            $data["vtiger_cvstdfilter"][] = $row;
        }

        return serialize($data);
    }

    public static function import($data, $viewname = false) {
        global $adb;

        $data = @unserialize($data);

        if(empty($data)) return false;

        $cvid = $adb->getUniqueID("vtiger_customview");
        $sql = "SELECT * FROM vtiger_customview WHERE cvid = ".$cvid;
        $result = $adb->query($sql);
        if($adb->num_rows($result) > 0) {
            $sql = "SELECT MAX(cvid) as max FROM vtiger_customview WHERE cvid = ".$cvid;
            $result = $adb->query($sql);

            $cvid = intval($adb->query_result($result, 0, "max")) + 1;
        }

        $data["vtiger_customview"]["cvid"] = $cvid;
        if($viewname !== false) {
            $data["vtiger_customview"]["viewname"] = $viewname;
        }
        $genSQL = self::createSQL($data["vtiger_customview"]);
        $sql = "INSERT INTO vtiger_customview SET ".$genSQL[0];
        $adb->pquery($sql, $genSQL[1]);

        $tables = array("vtiger_cvadvfilter","vtiger_cvadvfilter_grouping", "vtiger_cvcolumnlist", "vtiger_cvstdfilter");
        foreach($tables as $tablename) {
            foreach($data[$tablename] as $record) {
                $record["cvid"] = $cvid;
                $genSQL = self::createSQL($record);
                $sql = "INSERT INTO ".$tablename." SET ".$genSQL[0];
                $adb->pquery($sql, $genSQL[1]);
            }
        }

        return $cvid;
    }

    private static function createSQL($values) {
        $result = array(array(), array());
        foreach($values as $key => $value) {
            $result[0][] = "`".$key."` = ?";
            $result[1][] = $value;
        }

        $result[0] = implode(",", $result[0]);
        return $result;
    }
}