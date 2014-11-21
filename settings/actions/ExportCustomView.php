<?php

global $root_directory;
require_once($root_directory."/modules/SwVtTools/autoload_wf.php");

class Settings_SwVtTools_ExportCustomView_Action extends Settings_Vtiger_Basic_Action {

    function checkPermission(Vtiger_Request $request) {
   		return true;
   	}

    public function process(Vtiger_Request $request) {
        $adb = PearDatabase::getInstance();
        //$params = $request->getAll();
        $post = $request->getAll();

        if(!empty($post['filterId'])) {
            $filterId = $post['filterId'];
            $data = array();

            $columns = array();

            $sql = 'SELECT * FROM vtiger_customview WHERE cvid = ?';
            $data['vtiger_customview'] = $adb->fetchByAssoc($adb->pquery($sql, array($filterId)));

            $sql = 'SELECT * FROM vtiger_cvcolumnlist WHERE cvid = ?';
            $result = $adb->pquery($sql, array($filterId));

            while($row = $adb->fetchByAssoc($result)) {
                $data['vtiger_cvcolumnlist'][] = $row;

                $parts = explode(':', $row['columnname']);
                $columns[] = array($parts[0], $parts[2], $row['columnname']);
            }

            $sql = 'SELECT * FROM vtiger_cvstdfilter WHERE cvid = ?';
            $result = $adb->pquery($sql, array($filterId));

            while($row = $adb->fetchByAssoc($result)) {
                $data['vtiger_cvstdfilter'][] = $row;
            }

            $sql = 'SELECT * FROM vtiger_cvadvfilter_grouping WHERE cvid = ?';
            $result = $adb->pquery($sql, array($filterId));

            while($row = $adb->fetchByAssoc($result)) {
                $data['vtiger_cvadvfilter_grouping'][] = $row;
            }

            $sql = 'SELECT * FROM vtiger_cvadvfilter WHERE cvid = ?';
            $result = $adb->pquery($sql, array($filterId));

            while($row = $adb->fetchByAssoc($result)) {
                $data['vtiger_cvadvfilter'][] = $row;

                $parts = explode(':', $row['columnname']);
                $columns[] = array($parts[0], $parts[2], $row['columnname']);
            }

            foreach($columns as $col) {
                $sql = 'SELECT * FROM vtiger_field WHERE tablename = ? AND fieldname = ?';
                $result = $adb->pquery($sql, array($col[0], $col[1]));
                $field = $adb->fetchByAssoc($result);
                $data['columns'][] = array($field['fieldname'], \SwVtTools\VtUtils::getModuleName($field['tabid']), $field['fieldlabel'], $field['uitype'], $col[2]);
            }

            global $vtiger_current_version;
            //$vtiger_current_version = '6.0.0';
            $data['system'] = array('vtiger_version' => $vtiger_current_version);

            $data = base64_encode(serialize($data));

            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT\n");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

            header("Content-Disposition: attachment; filename=\"customview_".intval($filterId).".cv\";" );

            header("Content-Type: application/force-download");
            header('Content-Description: File Transfer');

            echo $data;
        }
    }

    public function validateRequest(Vtiger_Request $request) {
        $request->validateReadAccess();
    }
}