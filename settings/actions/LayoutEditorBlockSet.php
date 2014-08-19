<?php

global $root_directory;
require_once($root_directory."/modules/SwVtTools/autoload_wf.php");

class Settings_SwVtTools_LayoutEditorBlockSet_Action extends Settings_Vtiger_Basic_Action {

    function checkPermission(Vtiger_Request $request) {
   		return true;
   	}

    public function process(Vtiger_Request $request) {
        $adb = PearDatabase::getInstance();
        //$params = $request->getAll();

        $blockId = $request->get('blockid');
        $blockLabel = $request->get('blockLabel');

        $sql = 'UPDATE vtiger_blocks SET blocklabel = ? WHERE blockid = ?';
        $adb->pquery($sql, array($blockLabel, intval($blockId)));

        die(json_encode(array('blockLabel')));
    }
}