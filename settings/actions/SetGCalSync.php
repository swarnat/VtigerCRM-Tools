<?php

global $root_directory;
require_once($root_directory."/modules/SwVtTools/autoload_wf.php");

class Settings_SwVtTools_SetGCalSync_Action extends Settings_Vtiger_Basic_Action {

    function checkPermission(Vtiger_Request $request) {
   		return true;
   	}

    public function process(Vtiger_Request $request) {
        $adb = PearDatabase::getInstance();
        //$params = $request->getAll();

        $value = $request->get('value');

        $cron = array(
            'name' => 'SWVTTool GoogleCalSync',
            'handler_file' => 'modules/SwVtTools/cron/gcal_sync.service.php',
            'frequency' => '600',
            'module' => 'SwVtTools',
            'desc' => 'Check every 10 minutes if Calendar needs to be synced',
        );

        if($value == 'false') {
            Vtiger_Cron::deregister($cron['name']);
        } else {
            $sql = 'SELECT * FROM vtiger_cron_task WHERE name = ?';
            $result = $adb->pquery($sql, array($cron['name']));
            if($adb->num_rows($result) > 0) {
                $sql = 'UPDATE vtiger_cron_task SET status = 1, handler_file = "'.$cron['handler_file'].'" WHERE id = '.$adb->query_result($result, 0, 'id');
                $adb->query($sql);
            } else {
                Vtiger_Cron::register($cron['name'], $cron['handler_file'],$cron['frequency'], $cron['module'], 1, Vtiger_Cron::nextSequence(), $cron['desc']);
            }
        }

    }
}