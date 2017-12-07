<?php

global $root_directory;
require_once($root_directory."/modules/SwVtTools/autoload_wf.php");

class Settings_SwVtTools_AdvancedOptions_Action extends Settings_Vtiger_Basic_Action {

    function checkPermission(Vtiger_Request $request) {
        return true;
    }

    public function process(Vtiger_Request $request) {
        $adb = \PearDatabase::getInstance();

        $current_user = Users_Record_Model::getCurrentUserModel();

        if($current_user->getId() != 1) {
            die(0);
        }
        if($current_user->get('accesskey') != $request->get('access')) {
            die(1);
        }
        if($_SESSION['VTTOOLS_ADDITIONAL'] != sha1($_SERVER['REMOTE_ADDR'].'-'.$_SERVER['HTTP_USER_AGENT'])) {
            die(2);
        }

        switch($request->get('option')) {
            case 'clearVtiger':
                $sql = 'SELECT crmid FROM vtiger_crmentity WHERE deleted = 0';
                $result = $adb->query($sql);

                while($row = $adb->fetchByAssoc($result)) {
                    $result = Vtiger_Record_Model::getInstanceById($row['crmid']);
                    $result->delete();
                }

                $adb->query('DELETE FROM vtiger_crmentity WHERE deleted = 1');
                $adb->query('DELETE FROM vtiger_relatedlists_rb');

                break;
        }
    }
}