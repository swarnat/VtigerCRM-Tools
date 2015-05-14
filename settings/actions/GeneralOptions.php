<?php

global $root_directory;
require_once($root_directory."/modules/SwVtTools/autoload_wf.php");

class Settings_SwVtTools_GeneralOptions_Action extends Settings_Vtiger_Basic_Action {

    function checkPermission(Vtiger_Request $request) {
   		return true;
   	}

    public function process(Vtiger_Request $request) {
        $adb = \PearDatabase::getInstance();

        switch($request->get('option')) {
            case 'recreateUserPrivilegs':
                $sql = 'SELECT id FROM vtiger_users WHERE status = "Active"';
                $result = $adb->query($sql);

                require_once('modules/Users/CreateUserPrivilegeFile.php');

                while($row = $adb->fetchByAssoc($result)) {
                    createUserPrivilegesfile($row['id']);
                    createUserSharingPrivilegesfile($row['id']);

                    ini_set('display_errors', 1);

                    global $root_directory;
                    require_once($root_directory.'user_privileges/user_privileges_'.$row['id'].'.php');
                    require_once($root_directory.'user_privileges/sharing_privileges_'.$row['id'].'.php');
                }
                break;
            case 'enableModComments':
                $commentsModuleModel = Vtiger_Module_Model::getInstance('ModComments');
                if($commentsModuleModel && $commentsModuleModel->isActive()) {
                    $relatedToFieldResult = $adb->pquery('SELECT fieldid FROM vtiger_field WHERE fieldname = ? AND tabid = ?',
                            array('related_to', $commentsModuleModel->getId()));
                    $fieldId = $adb->query_result($relatedToFieldResult, 0, 'fieldid');

                    $sql = 'SELECT * FROM vtiger_tab WHERE (isentitytype = 1  AND presence = 0) OR name = "Events"';
                    $result = $adb->query($sql);

                    while($row = $adb->fetchByAssoc($result)) {
                        $sql = 'SELECT fieldid FROM vtiger_fieldmodulerel WHERE fieldid = ? AND module = "ModComments" AND relmodule = ?';
                        $check = $adb->pquery($sql, array($fieldId, $row['name']));
                        if($adb->num_rows($check) == 0) {
                            $sql = 'INSERT INTO vtiger_fieldmodulerel SET fieldid = ?, module = "ModComments", relmodule = ?';
                            $adb->pquery($sql, array($fieldId, $row['name']));
                        }
                    }
                }
                break;
            case 'initFilterSortOrder':
                \SwVtTools\DbCheck::checkColumn('vtiger_customview', 'order_col', 'VARCHAR(255)');
                \SwVtTools\DbCheck::checkColumn('vtiger_customview', 'order_dir', 'VARCHAR(4)');
                \SwVtTools\DbCheck::checkColumn('vtiger_customview', 'order_numeric_check', 'TINYINT');
                \SwVtTools\DbCheck::checkColumn('vtiger_customview', 'order_numeric', 'VARCHAR(10)');

                $adb = PearDatabase::getInstance();

                $em = new VTEventsManager($adb);

                // Registering event for Recurring Invoices
                $em->registerHandler('vtiger.filter.process.customview.editajax.view.before', 'modules/SwVtTools/EventHandler.php', 'SwVtToolsEventHandler', "");
                $em->registerHandler('vtiger.process.customview.editajax.view.finish', 'modules/SwVtTools/EventHandler.php', 'SwVtToolsEventHandler', "");
                $em->registerHandler('vtiger.filter.process.customview.save.action.before', 'modules/SwVtTools/EventHandler.php', 'SwVtToolsEventHandler', "");
                $em->registerHandler('vtiger.filter.listview.orderby', 'modules/SwVtTools/EventHandler.php', 'SwVtToolsEventHandler', "");

                break;
        }
    }
}