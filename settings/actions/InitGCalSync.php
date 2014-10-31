<?php

global $root_directory;
require_once($root_directory."/modules/SwVtTools/autoload_wf.php");

class Settings_SwVtTools_InitGCalSync_Action extends Settings_Vtiger_Basic_Action {

    function checkPermission(Vtiger_Request $request) {
   		return true;
   	}

    public function process(Vtiger_Request $request) {
        $adb = \PearDatabase::getInstance();

        if(!\SwVtTools\VtUtils::existTable('vtiger_gcal_sync')) {
            $adb->query("CREATE TABLE IF NOT EXISTS `vtiger_gcal_sync` (
                  `user_id` int(11) NOT NULL,
                  `calendar_id` varchar(500) NOT NULL
                ) ENGINE=InnoDB;");
        }
    }
}