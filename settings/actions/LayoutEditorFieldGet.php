<?php

global $root_directory;
require_once($root_directory."/modules/SwVtTools/autoload_wf.php");

class Settings_SwVtTools_LayoutEditorFieldGet_Action extends Settings_Vtiger_Basic_Action {

    function checkPermission(Vtiger_Request $request) {
   		return true;
   	}

    public function process(Vtiger_Request $request) {
        $adb = PearDatabase::getInstance();
        //$params = $request->getAll();

        $fieldId = $request->get('fieldid');
        $sql = 'SELECT fieldlabel FROM vtiger_field WHERE fieldid = ?';
        $result = $adb->pquery($sql, array(intval($fieldId)));
        $fieldLabel = $adb->query_result($result, 0, 'fieldlabel');

        die(json_encode(array('fieldLabel' => $fieldLabel)));
    }
}