<?php

global $root_directory;
require_once($root_directory."/modules/SwVtTools/autoload_wf.php");

class Settings_SwVtTools_LayoutEditorFieldSet_Action extends Settings_Vtiger_Basic_Action {

    function checkPermission(Vtiger_Request $request) {
   		return true;
   	}

    public function process(Vtiger_Request $request) {
        $adb = PearDatabase::getInstance();
        //$params = $request->getAll();

        $fieldId = $request->get('fieldid');
        $fieldLabel = $request->get('fieldLabel');

        $sql = 'UPDATE vtiger_field SET fieldlabel = ? WHERE fieldid = ?';
        $adb->pquery($sql, array($fieldLabel, intval($fieldId)));

        die();
    }
}