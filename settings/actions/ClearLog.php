<?php
global $root_directory;
require_once($root_directory."/modules/SwVtTools/autoload_wf.php");

class Settings_SWVtTools_ClearLog_Action extends Settings_Vtiger_Basic_Action {

    public function process(Vtiger_Request $request) {
        global $current_user;

        $adb = PearDatabase::getInstance();
        $type = $request->get('type');

        $adb->query('DELETE FROM vtiger_tools_logs WHERE `type` = "'.$type.'"');
    }

}

