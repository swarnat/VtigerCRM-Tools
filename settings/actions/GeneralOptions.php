<?php

global $root_directory;
require_once($root_directory."/modules/SwVtTools/autoload_wf.php");

class Settings_SwVtTools_GeneralOptions_Action extends Settings_Vtiger_Basic_Action {

    function checkPermission(Vtiger_Request $request) {
        return true;
    }

    function __construct() {
        parent::__construct();
        $this->exposeMethod('ReferenceFilterAdd');
        $this->exposeMethod('ReferenceFilterSave');
        $this->exposeMethod('ReferenceFilterDelete');
    }

    public function process(Vtiger_Request $request) {
        $adb = \PearDatabase::getInstance();

        $mode = $request->getMode();
        if (!empty($mode)) {
            echo $this->invokeExposedMethod($mode, $request);
            return;
        }

        switch($request->get('option')) {
            case 'germanDateFormatStep1':
                $sql = 'SELECT * FROM vtiger_date_format WHERE date_format = "dd.mm.yyyy"';
                $result = $adb->query($sql);

                if($adb->num_rows($result) == 0) {
                    $sql = 'INSERT INTO `vtiger_date_format` (`date_formatid`, `date_format`, `sortorderid`, `presence`) VALUES (4, "dd.mm.yyyy", 0, 1);';
                    $adb->query($sql);

                    echo 'Database changes done';
                } else {
                    echo 'No changes necessary';
                }

                break;
            case 'DeleteRelatedTabOrder':
                $sql = 'DELETE FROM vtiger_tools_reltab WHERE id = ?';
                $adb->pquery($sql, array($request->get('id')));
                break;
            case 'DeletePartialDetailView':
                $sql = 'DELETE FROM vtiger_tools_detailpart WHERE id = ?';
                $adb->pquery($sql, array($request->get('id')));
                break;
            case 'checkModuleFields':
                $value = $request->get('value');
                $tabid = getTabId($value['moduleName']);
                if(empty($tabid)) return '';

                $sql = 'SELECT * FROM vtiger_field WHERE tabid = "'.$tabid.'" ORDER BY tabid';
                $result = $adb->query($sql);

                $dbTypes = array ( 1 => 'VARCHAR(128)', 2 => 'VARCHAR(128)', 3 => 'INT(11)', 4 => 'VARCHAR(32)', 5 => 'DATE', 6 => 'TIMESTAMP NULL', 7 => 'INT(11)', 8 => 'VARCHAR(512)', 9 => 'DECIMAL(25,3)', 10 => 'INT(19)', 11 => 'VARCHAR(128)', 12 => 'VARCHAR(128)', 13 => 'VARCHAR(128)', 15 => 'VARCHAR(128)', 16 => 'VARCHAR(128)', 17 => 'VARCHAR(256)', 19 => 'TEXT', 20 => 'TEXT', 21 => 'VARCHAR(512)', 22 => 'VARCHAR(128)', 23 => 'DATE', 24 => 'VARCHAR(255)', 25 => 'VARCHAR(128)', 26 => 'VARCHAR(128)', 27 => 'VARCHAR(128)', 28 => 'VARCHAR(128)', 30 => 'TIMESTAMP NULL', 33 => 'VARCHAR(128)', 51 => 'INT(11)', 52 => 'VARCHAR(128)', 53 => 'VARCHAR(128)', 55 => 'VARCHAR(128)', 56 => 'VARCHAR(5)', 57 => 'INT(11)', 69 => 'VARCHAR(256)', 70 => 'DATETIME', 71 => 'DECIMAL(25,3)', 72 => 'DECIMAL(25,8)', 73 => 'INT(11)', 76 => 'INT(11)', 77 => 'INT(11)', 83 => 'DECIMAL(7,3)', 117 => 'VARCHAR(128)', 255 => 'VARCHAR(128)', );
                $typeofdatas = array ( 1 => 'V', 2 => 'V', 3 => 'I', 4 => 'V', 5 => 'D', 6 => 'DT', 7 => 'I', 8 => 'V', 9 => 'N', 10 => 'V', 11 => 'V', 12 => 'E', 13 => 'E', 15 => 'V', 16 => 'V', 17 => 'V', 19 => 'V', 20 => 'V', 21 => 'V', 22 => 'V', 23 => 'D', 24 => 'V', 25 => 'V', 26 => 'V', 27 => 'V', 28 => 'V', 30 => 'DT', 33 => 'V', 51 => 'V', 52 => 'V', 53 => 'V', 55 => 'V', 56 => 'C', 57 => 'V', 69 => 'V', 70 => 'D', 71 => 'N', 72 => 'N', 73 => 'V', 76 => 'V', 77 => 'V', 83 => 'N', 117 => 'V', 255 => 'V', );

                ob_start();
                while($row = $adb->fetchByAssoc($result)) {

                    \SwVtTools\VtUtils::checkColumn($row['tablename'], $row['columnname'], $dbTypes[$row['uitype']]);

                    if(empty($row['typeofdata'])) {
                        $sql = 'UPDATE vtiger_field SET typeofdata = "'.$typeofdatas[$row['uitype']].'~O" WHERE fieldid = '.$row['fieldid'];
                        $adb->query($sql);
                    }
                }
                $return = ob_get_clean();
                if(empty($return)) {
                    echo 'Result: No columns added';
                } else {
                    echo str_replace('<br>', PHP_EOL, $return);
                }
                break;
            case 'gsync_test':
                require_once('modules/SwVtTools/cron/gcal_sync.service.php');
                break;
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
                $ignore = array('Webmails' => true);
                $sql = 'SELECT * FROM vtiger_fieldmodulerel WHERE module = "ModComments"';
                $result = $adb->query($sql);
                while($row = $adb->fetchByAssoc($result)) {
                    if(isset($ignore[$row['relmodule']])) continue;

                    $testObj = CRMEntity::getInstance($row['relmodule']);

                    if(!isset($testObj->tab_name)) {
                        $sql = 'DELETE FROM vtiger_fieldmodulerel WHERE module = "ModComments" AND relmodule = "'.$row['relmodule'].'"';
                        $result = $adb->query($sql);
                    }
                }

                $commentsModuleModel = Vtiger_Module_Model::getInstance('ModComments');
                if($commentsModuleModel && $commentsModuleModel->isActive()) {
                    $relatedToFieldResult = $adb->pquery('SELECT fieldid FROM vtiger_field WHERE fieldname = ? AND tabid = ?',
                        array('related_to', $commentsModuleModel->getId()));
                    $fieldId = $adb->query_result($relatedToFieldResult, 0, 'fieldid');

                    $sql = 'SELECT * FROM vtiger_tab WHERE (isentitytype = 1  AND presence = 0) OR name = "Events"';
                    $result = $adb->query($sql);


                    while($row = $adb->fetchByAssoc($result)) {
                        if(isset($ignore[$row['name']])) continue;

                        $testObj = CRMEntity::getInstance($row['name']);

                        if(!isset($testObj->tab_name)) continue;

                        $sql = 'SELECT fieldid FROM vtiger_fieldmodulerel WHERE fieldid = ? AND module = "ModComments" AND relmodule = ?';
                        $check = $adb->pquery($sql, array($fieldId, $row['name']));
                        if($adb->num_rows($check) == 0) {
                            $sql = 'INSERT INTO vtiger_fieldmodulerel SET fieldid = ?, module = "ModComments", relmodule = ?';
                            $adb->pquery($sql, array($fieldId, $row['name']));
                        }
                    }
                }
                break;
            case 'removeMarketplaceBanner':
                $sql = 'DELETE FROM vtiger_links WHERE linkurl = "modules/ExtensionStore/ExtensionStore.js"';
                $adb->pquery($sql, array());
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

    public function ReferenceFilterDelete(Vtiger_Request $request) {
        $adb = \PearDatabase::getInstance();

        $sql = 'DELETE FROM vtiger_tools_referencefilter WHERE id = ?';

        $adb->pquery($sql, array($request->get('id')));
    }

    public function ReferenceFilterSave(Vtiger_Request $request) {
        $adb = \PearDatabase::getInstance();
        $id = $request->get('id');
        $condition = $request->get('condition');
        $parts = explode("\n", $condition);

        $sql = 'SELECT modulename FROM vtiger_tools_referencefilter WHERE id = ?';
        $result = $adb->pquery($sql, array($id));
        $moduleName = $adb->query_result($result, 0, 'modulename');

        $resultCondition = array();
        foreach($parts as $part) {
            $split = explode(';', trim($part), 3);

            $fieldInfo = \SwVtTools\VtUtils::getFieldInfo($split[0], getTabid($moduleName));
            $resultCondition[] = array(
                'field' => $split[0],
                'operator' => $split[1],
                'value' => $split[2],
                'column' => $fieldInfo['columnname'],
            );
        }

        $sql = 'UPDATE vtiger_tools_referencefilter SET `condition` = ? WHERE id = ?';
        $adb->pquery($sql, array(json_encode($resultCondition), $id));

        $this->registerReferenceFilter();
    }

    public function ReferenceFilterAdd(Vtiger_Request $request) {
        $adb = \PearDatabase::getInstance();
        $parts = explode('-', $request->get('field'));

        $sql = 'INSERT INTO vtiger_tools_referencefilter SET `modulename` = ?, `field` = ?, tomodule = ?, `condition` = ?';

        $adb->pquery($sql, array($parts[0], $parts[1], $parts[2], json_encode(array())));

        $this->registerReferenceFilter();
    }

    public function registerReferenceFilter() {
        $adb = \PearDatabase::getInstance();

        $sql = 'SELECT linkid FROM vtiger_links WHERE linktype = "HEADERSCRIPT" AND linkurl = "modules/SwVtTools/resources/referencefilter.js"';
        $result = $adb->query($sql);

        if($adb->num_rows($result) == 0) {
            $link_module = Vtiger_Module::getInstance("SwVtTools");
            $link_module->addLink('HEADERSCRIPT','ToolsGermanNumbers',"modules/SwVtTools/resources/referencefilter.js");
        }

        vimport('~~include/events/include.inc');
        $em = new VTEventsManager($adb);

        $em->registerHandler('vtiger.filter.listview.querygenerator.before', 'modules/SwVtTools/EventHandler.php', 'SwVtToolsEventHandler');
        $em->registerHandler('vtiger.filter.searchrecords.query', 'modules/SwVtTools/EventHandler.php', 'SwVtToolsEventHandler');

    }
}