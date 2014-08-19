<?php

global $root_directory;
require_once($root_directory."/modules/SwVtTools/autoload_wf.php");

class Settings_SwVtTools_LayoutEditorBlockGet_Action extends Settings_Vtiger_Basic_Action {

    function checkPermission(Vtiger_Request $request) {
   		return true;
   	}

    public function process(Vtiger_Request $request) {
        $adb = PearDatabase::getInstance();
        //$params = $request->getAll();

        $blockId = $request->get('blockid');

        $sql = 'SELECT blocklabel FROM vtiger_blocks WHERE blockid = ?';
        $result = $adb->pquery($sql, array(intval($blockId)));
        $blockLabel = $adb->query_result($result, 0, 'blocklabel');

        die(json_encode(array('blockLabel' => $blockLabel)));
    }
}