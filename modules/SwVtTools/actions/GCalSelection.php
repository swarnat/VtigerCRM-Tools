<?php
use \Workflow\VTEntity;
use \Workflow\VTTemplate;

global $root_directory;
require_once($root_directory."/modules/SwVtTools/autoload_wf.php");

class SwVtTools_GCalSelection_Action extends Vtiger_Action_Controller {

    function checkPermission(Vtiger_Request $request) {
   		return true;
   	}

    public function process(Vtiger_Request $request) {
        $adb = PearDatabase::getInstance();

        $currentUser = Users_Record_Model::getCurrentUserModel();

        $calendarId = str_replace('http://www.google.com/calendar/feeds/default/', '', $request->get('calendarId'));
        $sql = 'INSERT INTO vtiger_gcal_sync SET user_id = ?, calendar_id = ?';
        $adb->pquery($sql, array($currentUser->id, $calendarId));

        exit();
    }
}