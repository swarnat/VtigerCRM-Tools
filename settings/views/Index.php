<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
global $root_directory;
require_once($root_directory."/modules/SwVtTools/autoload_wf.php");

class Settings_SwVtTools_Index_View extends Settings_Vtiger_Index_View {

    private function _generateSQLValues($data, $whitelist = null) {
        $params = array();
        $sqlArray = array();
        foreach($data as $key => $value) {
            if($whitelist === null || in_array($key, $whitelist)) {
                $sqlArray[] = '`'.$key.'` = ?';
                $params[] = $value;
            }
        }
        $sql = implode(',', $sqlArray);

        return array('query' => $sql, 'bind' => $params);
    }

	public function process(Vtiger_Request $request) {
        $adb = \PearDatabase::getInstance();

        $viewer = $this->getViewer($request);

        if(!empty($_GET['delSidebar'])) {
            $sql = "DELETE FROM vtiger_tools_sidebar WHERE id = ".intval($_GET["delSidebar"]);
            $adb->query($sql);

            $linkurl = 'module=SwVtTools&view=SidebarWidget&sID='.intval($_GET["delSidebar"]).'';
            $sql = "DELETE FROM vtiger_links WHERE linkurl = '".$linkurl."'";
            $adb->query($sql);
        }

        if(!empty($_POST['tool_action'])) {
            switch($_POST['tool_action']) {
                case 'saveSidebar':
                    $sql = "UPDATE vtiger_tools_sidebar SET title = ?, content = ?, active = ? WHERE id = ?";
                    $adb->pquery($sql, array($_POST["title"], $_POST["content"], $_POST["active"]=="1"?1:0, $_POST["sidebar_id"]));

                    $linkurl = 'module=SwVtTools&view=SidebarWidget&sID='.intval($_POST["sidebar_id"]).'';
                    echo '<div class="alert alert-success" style="padding:10px;">Sidebar was saved successfully!</div>';
                    if($_POST["active"] == "1") {
                        $sql = "SELECT * FROM vtiger_links WHERE linkurl = ?";
                        $result = $adb->pquery($sql, array($linkurl));

                        if($adb->num_rows($result) == 0) {
                            $linkid = $adb->getUniqueID("vtiger_links");
                            $sql = "INSERT INTO vtiger_links SET linkid = ".$linkid.", tabid = ?, linktype = 'DETAILVIEWSIDEBARWIDGET', linklabel = ?, linkurl = ?";
                            $adb->pquery($sql, array(intval($_POST["sidebar_tabid"]), $_POST["title"], $linkurl));
                        } else {

                        }
                    } else {
                        $sql = "DELETE FROM vtiger_links WHERE linkurl = ?";
                        $adb->pquery($sql, array($linkurl));
                    }

                    break;
                case 'createSidebar':
                    $nextID = $adb->getUniqueID("vtiger_tools_sidebar");

                    $sql = "INSERT INTO vtiger_tools_sidebar SET id = ".$nextID.", active = 0, tabid = ".intval($_POST["sidebar_module"]).", content = '', title = 'Sidebar ".$nextID."'";
                    $adb->query($sql);

                    break;
                case 'createRelation':
                    include_once('vtlib/Vtiger/Module.php');

                    if(empty($_POST['label'])) {
                        echo '<div class="alert alert-danger" style="padding:10px;">You don\'t set relation label!</div>';
                    } else {

                        $fromInstance = Vtiger_Module::getInstance(\SwVtTools\VtUtils::getModuleName($_POST['tabid']));
                        $toModuleName = \SwVtTools\VtUtils::getModuleName($_POST['related_tabid']);
                        $toInstance = Vtiger_Module::getInstance($toModuleName);

                        $reltype = $_POST['reltype'] == 'get_dependents_list' ? 'get_dependents_list' : 'get_related_list';

                        $fromInstance->setRelatedlist($toInstance,$_POST['label'],array('add','select'), $toModuleName=='Documents'?'get_attachments':$reltype);
                        echo '<div class="alert alert-success" style="padding:10px;">Relation was created</div>';
                    }
                    break;
                case 'makeCvToDefault':
                    $defaultCustomView = array();
                    foreach($_POST['filterIds'] as $filterId) {
                        $sql = 'SELECT entitytype FROM vtiger_customview WHERE cvid = '.$filterId;
                        $result = $adb->query($sql);
                        if($adb->num_rows($result) == 0) {
                            continue;
                        }

                        $defaultCustomView[getTabId($adb->query_result($result, 0, 'entitytype'))] = $filterId;
                    }
                    foreach($_POST['userIds'] as $userId) {
                        foreach($defaultCustomView as $tabid => $cvid) {
                            $sql = 'REPLACE INTO vtiger_user_module_preferences SET userid = '.$userId.', tabid = '.$tabid.',default_cvid = '.$cvid;
                            $adb->query($sql, true);
                        }
                    }
                    break;
                case 'cv_import1':
                    if(is_uploaded_file($_FILES['customview']['tmp_name'])) {
                        $content = base64_decode(trim(file_get_contents($_FILES['customview']['tmp_name'])));
                        $key = md5($content.microtime(false));

                        $content = unserialize($content);
                        global $vtiger_current_version;
                        if($vtiger_current_version != $content['system']['vtiger_version']) {
                            $viewer->assign('showCVImportError', 'The exported vtiger version ['.$content['system']['vtiger_version'].'] does not match with the current one ['.$vtiger_current_version.']! This isn\'t Possible');
                        } else {
                            $viewer->assign('showCVImportError', false);

                            $entityType = $request->get('cvImportModule');
                            if(empty($entityType)) {
                                $entityType = $content['vtiger_customview']['entitytype'];
                            }
                            $content['entityType'] = $entityType;

                            $_SESSION['filterimport'][$key] = $content;

                            $viewer->assign('importKey', $key);

                            $columns = $content['columns'];
                            $viewer->assign('filter_columns', $content['columns']);


                            $availableFields = \SwVtTools\VtUtils::getFieldsForModule($entityType);
                            $fields = array();
                            foreach($availableFields as $field) {
                                $fields[$field->name] = $field;
                            }
                            foreach($columns as $index => $value) {
                                if(isset($fields[$value[0]])) {
                                    $columns[$index][5] = $value[0];
                                }
                            }

                            $viewer->assign('import_available_fields', $availableFields);
                            $viewer->assign('cvImportColumns', $columns);
                        }
                    }
                    break;
                case 'cv_import2':
                    $content = $_SESSION['filterimport'][$request->get('cvImportKey')];
                    $columns = $request->get('column');

                    $moduleModel = Vtiger_Module_Model::getInstance($content['entityType']);

                    $resultColumns = array();
                    foreach($content['columns'] as $index => $value) {
                        $fieldInfo = $moduleModel->getField($columns[$value[0]]);
                        $columnName = $fieldInfo->getCustomViewColumnName();
                        //$content['columns'][$index][4] = $columnName;
                        $resultColumns[$value[4]] = $columnName;
                    }

                    $cvid = $adb->getUniqueID('vtiger_customview');

                    $current_user = Users_Record_Model::getCurrentUserModel();
                    $whiteList = array('cvid','viewname', 'setdefault', 'setmetrics', 'entitytype', 'status');
                    $content['vtiger_customview']['userid'] = $current_user->getId();
                    $content['vtiger_customview']['viewname'] = $request->get('filterName');
                    $content['vtiger_customview']['cvid'] = $cvid;

                    $query = $this->_generateSQLValues($content['vtiger_customview'], $whiteList);
                    $adb->pquery('INSERT INTO vtiger_customview SET '.$query['query'], $query['bind'], true);

                    $whiteList = array('cvid', 'columnindex', 'columnname');
                    foreach($content['vtiger_cvcolumnlist'] as $record) {
                        $record['cvid'] = $cvid;
                        $record['columnname'] = $resultColumns[$record['columnname']];

                        $query = $this->_generateSQLValues($record, $whiteList);
                        $adb->pquery('INSERT INTO vtiger_cvcolumnlist SET '.$query['query'], $query['bind'], true);
                    }

                    if(!empty($content['vtiger_cvstdfilter']) && is_array($content['vtiger_cvstdfilter'])) {
                        $whiteList = array('cvid', 'columnindex', 'stdfilter', 'startdate', 'enddate');
                        foreach($content['vtiger_cvstdfilter'] as $record) {
                            $record['cvid'] = $cvid;
                            $record['columnname'] = $resultColumns[$record['columnname']];

                            $query = $this->_generateSQLValues($record, $whiteList);
                            $adb->pquery('INSERT INTO vtiger_cvstdfilter SET '.$query['query'], $query['bind'], true);
                        }
                    }

                    if(!empty($content['vtiger_cvadvfilter_grouping']) && is_array($content['vtiger_cvadvfilter_grouping'])) {
                        $whiteList = array('cvid', 'groupid', 'group_condition', 'condition_expression');
                        foreach($content['vtiger_cvadvfilter_grouping'] as $record) {
                            $record['cvid'] = $cvid;

                            $query = $this->_generateSQLValues($record, $whiteList);
                            $adb->pquery('INSERT INTO vtiger_cvadvfilter_grouping SET '.$query['query'], $query['bind'], true);
                        }
                    }

                    if(!empty($content['vtiger_cvadvfilter']) && is_array($content['vtiger_cvadvfilter'])) {
                        $whiteList = array('cvid', 'columnindex', 'columnname', 'comparator', 'value', 'groupid', 'column_condition');
                        foreach($content['vtiger_cvadvfilter'] as $record) {
                            $record['cvid'] = $cvid;
                            $record['columnname'] = $resultColumns[$record['columnname']];

                            $query = $this->_generateSQLValues($record, $whiteList);
                            $adb->pquery('INSERT INTO vtiger_cvadvfilter SET '.$query['query'], $query['bind'], true);
                        }
                    }

                    break;
            }
        }

        if(!empty($_GET['editSidebar'])) {
            $sql = 'SELECT * FROM vtiger_tools_sidebar WHERE id = ?';
            $result = $adb->pquery($sql, array($_GET["editSidebar"]), true);
            $sidebarData = $adb->fetchByAssoc($result);
            $sidebarData['moduleName'] = \SwVtTools\VtUtils::getModuleName($sidebarData['tabid']);

            $viewer->assign('editSidebar', $sidebarData);
        }

        $moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

        $sql = 'SELECT user_name, id, first_name, last_name FROM vtiger_users';
        $result = $adb->query($sql, true);

        while($row = $adb->fetchByAssoc($result)) {
            $users[] = $row;
        }
        $viewer->assign('availableUsers', $users);

        $sql = 'SELECT * FROM vtiger_links WHERE linktype = "HEADERSCRIPT" and linklabel = "ToolsGermanNumbers" LIMIT 1';
        $result = $adb->pquery($sql);
        if($adb->num_rows($result) > 0) {
            $viewer->assign('comma_numbers_enabled', true);
        } else {
            $viewer->assign('comma_numbers_enabled', false);
        }

        $sql = 'SELECT * FROM vtiger_cron_task WHERE name = ?';
        $result = $adb->pquery($sql, array('SWVTTool GoogleCalSync'));
        if($adb->num_rows($result) > 0) {
            $viewer->assign('gcal_autosync', true);
        } else {
            $viewer->assign('gcal_autosync', false);
        }

        $entityModules = \SwVtTools\VtUtils::getEntityModules(true);

        $viewer->assign('entityModules', $entityModules);

        $sql = 'SELECT * FROM vtiger_customview ORDER BY entitytype';
        $result = $adb->query($sql, true);

        $customViews = array();
        while($filter = $adb->fetchByAssoc($result)) {
            $customViews[$filter['cvid']] = $filter['entitytype'].' - '.$filter['viewname'];
        }

        $viewer->assign('customViews', $customViews);

        $sql = 'SELECT * FROM vtiger_tools_sidebar ORDER BY tabid';
        $result = $adb->query($sql, true);

        $sidebars = array();
        while($row = $adb->fetchByAssoc($result)) {
            $row['moduleName'] = \SwVtTools\VTUtils::getModuleName($row["tabid"]);
            $sidebars[] = $row;
        }

        $sql = 'SELECT MAX(laststart) as timestart FROM vtiger_cron_task';
        $result = $adb->query($sql);
        if(time() - $adb->query_result($result,0, 'timestart') > 86400) {
            $viewer->assign('show_cron_warning', true);
        }

        $EventHandlerActive = class_exists('EventHandler_Module_Model') && vtlib_isModuleActive('EventHandler') && strpos(file_get_contents(vglobal('root_directory').'/modules/Vtiger/models/ListView.php'), 'EventHandler_Module_Model::do_filter') !== false;
        $viewer->assign('EventHandlerActive', $EventHandlerActive);

        $viewer->assign('sidebars', $sidebars);

        $viewer->view('Index.tpl', $qualifiedModuleName);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param Vtiger_Request $request
	 * @return <Array> - List of Vtiger_JsScript_Model instances
	 */
	function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			"modules.Settings.$moduleName.views.resources.backend",
			"modules.Settings.$moduleName.views.resources.Essentials",
            "libraries.jquery.ckeditor.ckeditor",
            "libraries.jquery.ckeditor.adapters.jquery",
            'modules.Vtiger.resources.CkEditor',
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);

        return $headerScriptInstances;
	}
    function getHeaderCss(Vtiger_Request $request) {
        $headerScriptInstances = parent::getHeaderCss($request);
        $moduleName = $request->getModule();

        $cssFileNames = array(
            "~/modules/Settings/$moduleName/views/resources/Backend.css"
        );

        $cssScriptInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerStyleInstances = array_merge($headerScriptInstances, $cssScriptInstances);
        return $headerStyleInstances;
    }
}