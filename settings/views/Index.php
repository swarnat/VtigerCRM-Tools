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

    private function getTabLabels($ids) {
        $adb = \PearDatabase::getInstance();
        $tablabels = array();

        foreach($ids as $part) {
            $splitter = explode('-', $part);
            switch($splitter[0]) {
                case 'tab':
                    switch($splitter[1]) {
                        case 'history':
                            $tablabels[] = 'LBL_UPDATES';
                            break;
                        case 'comments':
                            $tablabels[] = 'ModComments';
                            break;
                    }

                    break;
                case 'part':
                    $sql = 'SELECT id, title FROM vtiger_tools_detailpart WHERE id = '.$splitter[1];
                    $result = $adb->query($sql);

                    $tablabels[] = html_entity_decode($adb->query_result($result, 0, 'title'));

                    break;
                case 'rel':
                    $sql = 'SELECT label FROM vtiger_relatedlists WHERE relation_id = '.$splitter[1];
                    $availableResult = $adb->pquery($sql);
                    $tablabels[] = html_entity_decode($adb->query_result($availableResult, 0, 'label'));

                    break;
            }
        }
        return $tablabels;
    }
    public function delete_folder($tmp_path){
        if(!is_writeable($tmp_path) && is_dir($tmp_path)) {
            chmod($tmp_path,0777);
        }
        $handle = opendir($tmp_path);
        while($tmp=readdir($handle)) {
            if($tmp!='..' && $tmp!='.' && $tmp!=''){
                if(is_writeable($tmp_path.DS.$tmp) && is_file($tmp_path.DS.$tmp)) {
                    checkFileAccessForInclusion($tmp_path.DS.$tmp);
                    //echo $tmp_path.DS.$tmp.'<br/>';
                    unlink($tmp_path.DS.$tmp);
                } elseif(!is_writeable($tmp_path.DS.$tmp) && is_file($tmp_path.DS.$tmp)){
                    checkFileAccessForInclusion($tmp_path.DS.$tmp);
                    echo $tmp_path.DS.$tmp.'<br/>';
                    chmod($tmp_path.DS.$tmp,0666);
                    unlink($tmp_path.DS.$tmp);
                }

                if(is_writeable($tmp_path.DS.$tmp) && is_dir($tmp_path.DS.$tmp)) {
                    $this->delete_folder($tmp_path.DS.$tmp);
                } elseif(!is_writeable($tmp_path.DS.$tmp) && is_dir($tmp_path.DS.$tmp)){
                    chmod($tmp_path.DS.$tmp,0777);
                    $this->delete_folder($tmp_path.DS.$tmp);
                }
            }
        }
        closedir($handle);
        rmdir($tmp_path);
        if(!is_dir($tmp_path)) {
            return true;
        } else {
            return false;
        }
    }

	public function process(Vtiger_Request $request) {
        $adb = \PearDatabase::getInstance();

        $viewer = $this->getViewer($request);

        if(!empty($_REQUEST['clearworkflowdesigner'])) {
            $paths = array(
                vglobal('root_directory').'modules/Workflow2',
                vglobal('root_directory').'layouts/vlayout/modules/Workflow2',
            );
            foreach($paths as $path) {
                $this->delete_folder($path);
            }

        }

        if(!empty($_GET['delSidebar'])) {
            $sql = "DELETE FROM vtiger_tools_sidebar WHERE id = ".intval($_GET["delSidebar"]);
            $adb->query($sql);

            $linkurl = 'module=SwVtTools&view=SidebarWidget&sID='.intval($_GET["delSidebar"]).'';
            $sql = "DELETE FROM vtiger_links WHERE linkurl = '".$linkurl."'";
            $adb->query($sql);
        }

        $loadModuleFields = array();

        $obj = new SwVtTools();
        $obj->checkDB();

        if(!empty($_POST['tool_action'])) {
            switch($_POST['tool_action']) {
                case 'save_listviewwidget':
                    foreach($_POST['widget'] as $widgetId => $widgetData) {
                        $data = array(
                            'fields' => $widgetData['fields']
                        );

                        $sql = 'UPDATE vtiger_tools_listwidget SET active = ?, title = ?, settings = ? WHERE id = ?';
                        $adb->pquery($sql, array(!empty($widgetData['active'])?1:0, $widgetData['title'], json_encode($data), $widgetId));

                        $sql = 'SELECT linkid FROM vtiger_links WHERE  linktype = "LISTVIEWSIDEBARWIDGET" AND linkurl = "module=SwVtTools&view=ListViewQuickSearchWidget&widgetmodule='.$widgetData['module'].'&widgetid='.$widgetId.'"';
                        $result = $adb->pquery($sql);

                        if($adb->num_rows($result) > 0) {
                            if (empty($widgetData['active'])) {
                                $sql = 'DELETE FROM vtiger_links WHERE linktype = "LISTVIEWSIDEBARWIDGET" AND linkurl = "module=SwVtTools&view=ListViewQuickSearchWidget&widgetmodule='.$widgetData['module'].'&widgetid=' . $widgetId . '"';
                                $adb->pquery($sql);
                            } else {
                                $sql = 'UPDATE vtiger_links SET linklabel = ? WHERE  linktype = "LISTVIEWSIDEBARWIDGET" AND linkurl = "module=SwVtTools&view=ListViewQuickSearchWidget&widgetmodule='.$widgetData['module'].'&widgetid=' . $widgetId . '"';
                                $adb->pquery($sql, array($widgetData['title']));
                            }
                        } elseif (!empty($widgetData['active'])) {
                            Vtiger_Link::addLink(
                                getTabid($widgetData['module']),
                                "LISTVIEWSIDEBARWIDGET",
                                $widgetData['title'],
                                'module=SwVtTools&view=ListViewQuickSearchWidget&widgetmodule='.$widgetData['module'].'&widgetid=' . $widgetId,
                                "", "2", "");
                        }
                    }
                    break;
                case 'add_listviewwidget':
                    $moduleName = $request->get('modulename');
                    $sql = 'INSERT INTO vtiger_tools_listwidget SET active = 0, title = "Quick Search", module = ?, settings = ?';
                    $adb->pquery($sql, array($moduleName, json_encode(array())));
                    break;
                case 'add_reltab_order':
                    $adb = \PearDatabase::getInstance();
                    $em = new VTEventsManager($adb);
                    $em->registerHandler('vtiger.filter.detailview.relatedtabs', 'modules/SwVtTools/EventHandler.php', 'SwVtToolsEventHandler');

                    $tmp = array();

                    $sql = 'SELECT id, title FROM vtiger_tools_detailpart WHERE modulename = "'.$_POST['modulename'].'" AND title != "_default"';
                    $result = $adb->query($sql);
                    while($row = $adb->fetchByAssoc($result)) {
                        $tmp[] = 'part-'.$row['id'];
                    }

                    //$tmp[] = 'tab-touchpoints';
                    $tmp[] = 'tab-comments';

                    $sql = 'SELECT * FROM vtiger_relatedlists WHERE tabid = ? ORDER BY sequence';
                    $availableResult = $adb->pquery($sql, array(getTabid($_POST['modulename'])));
                    while($available = $adb->fetchByAssoc($availableResult)) {
                        $tmp[] = 'rel-'.$available['relation_id'];
                    }

                    //$tmp[] = 'tab-history';

                    $saveContent = array(
                        'ids' => $tmp,
                        'labels' => $this->getTabLabels($tmp),
                    );
                    $sql = 'INSERT INTO vtiger_tools_reltab SET modulename = ?, relations = ?';
                    $adb->pquery($sql, array($_POST['modulename'], json_encode($saveContent)));

                    header('Location:index.php?module=SwVtTools&view=Index&parent=Settings&tab=tab3');
                    exit();
                    break;
                case 'save_reltab_order':

                    foreach($_POST['reltaborder'] as $modulename => $data) {
                        $parts = explode(',', $data['relations']);

                        $saveContent = array(
                            'ids' => $parts,
                            'labels' => $this->getTabLabels($parts)
                        );
                        $sql = 'UPDATE vtiger_tools_reltab SET relations = ? WHERE modulename = ?';
                        $adb->pquery($sql, array(json_encode($saveContent), $modulename));
                    }

                    break;
                case 'save_detailviewpart':
                    $adb = \PearDatabase::getInstance();
                    $em = new VTEventsManager($adb);
                    $em->registerHandler('vtiger.filter.detailview.blocks.sql', 'modules/SwVtTools/EventHandler.php', 'SwVtToolsEventHandler');
                    $em->registerHandler('vtiger.filter.detailview.relatedtabs', 'modules/SwVtTools/EventHandler.php', 'SwVtToolsEventHandler');

                    foreach($_POST['detailviewpart'] as $id => $data) {
                        $sql = 'UPDATE vtiger_tools_detailpart SET title = ?, blockids = ? WHERE id = ?';
                        $adb->pquery($sql, array($data['title'], $data['blockids'], intval($id)));
                    }
                    break;
                case 'add_detailview_part':
                    $adb = \PearDatabase::getInstance();
                    $em = new VTEventsManager($adb);
                    $em->registerHandler('vtiger.filter.detailview.blocks.sql', 'modules/SwVtTools/EventHandler.php', 'SwVtToolsEventHandler');
                    $em->registerHandler('vtiger.filter.detailview.relatedtabs', 'modules/SwVtTools/EventHandler.php', 'SwVtToolsEventHandler');

                    $sql = 'SELECT MAX(sort) as max FROM vtiger_tools_detailpart WHERE modulename = ?';
                    $result = $adb->pquery($sql, array($_POST['modulename']));
                    if($adb->num_rows($result) > 0) {
                        $sort = intval($adb->query_result($result, 0, 'max'));
                    } else {
                        $sort = 0;
                    }
                    $sort++;

                    $sql = 'INSERT INTO vtiger_tools_detailpart SET modulename = ?, sort = ?, title = ?, blockids = ?, active = 0';
                    $adb->pquery($sql, array($_POST['modulename'], $sort, 'special Details', ''));
                    break;
                case 'switchUser':
                    $newUser = intval($_REQUEST['user']);
                    $_SESSION['authenticated_user_id'] = $newUser;
                    Vtiger_Session::set('AUTHUSERID', $newUser);

                    header('Location:index.php');
                    exit();
                    break;
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

        if($_REQUEST['ADDITIONAL'] == 'true') {
            $current_user = Users_Record_Model::getCurrentUserModel();

            if($current_user->getId() == 1) {

                if(!isset($_SESSION['VTTOOLS_ADDITIONAL'])) {
                    $_SESSION['VTTOOLS_ADDITIONAL'] = array();
                }

                $key = sha1($_SERVER['REMOTE_ADDR'].'-'.$_SERVER['HTTP_USER_AGENT']);
                $_SESSION['VTTOOLS_ADDITIONAL'] = $key;

                $viewer->assign('SHOW_ADDITIONAL', true);
            } else {
                $viewer->assign('SHOW_ADDITIONAL', false);
            }
        } else {
            $viewer->assign('SHOW_ADDITIONAL', false);
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

        $sql = 'SELECT * FROM vtiger_tools_listwidget ORDER BY module, title';
        $result = $adb->query($sql);
        $listwidgets = array();
        while($row = $adb->fetchByAssoc($result)) {
            $loadModuleFields[$row['module']] = true;
            $row['settings'] = json_decode(html_entity_decode($row['settings']), true);
            $listwidgets[] = $row;
        }
        $viewer->assign('listwidgets', $listwidgets);

        $sql = 'SELECT fieldname, fieldlabel, tabid, uitype FROM vtiger_field WHERE (uitype = 10 OR uitype = 51 OR uitype = 101 OR uitype = 57 OR uitype = 58 OR uitype = 59 OR uitype = 73 OR uitype = 75 OR uitype = 76 OR uitype = 78 OR uitype = 80 OR uitype = 81 OR uitype = 68) AND presence = 2 ORDER BY tabid';
        $result = $adb->query($sql);

        $referenceFields = array();

        while($row = $adb->fetchByAssoc($result)) {
            $fieldModuleName = \SwVtTools\VtUtils::getModuleName($row['tabid']);
            $references = \SwVtTools\VtUtils::getModuleForReference($row['tabid'], $row['fieldname'], $row['uitype']);
            foreach($references as $ref) {
                $key = $fieldModuleName . '-' . $row['fieldname'] . '-'.$ref;
                $referenceFields[$key] = vtranslate($fieldModuleName, $fieldModuleName) . ' - ' . vtranslate($row['fieldlabel'], $fieldModuleName) . ' - ' . vtranslate($ref, $ref);
            }
        }
        $viewer->assign('referenceFields', $referenceFields);

        if(\SwVtTools\Patcher::isPatchApplied(vglobal('root_directory').'/modules/'.$request->get('module').'/patcher/patches/partialdetailview.patch', array('partdetail_1', 'partdetail_2')) == false) {
            $viewer->assign('PartialDetailViewModificationRequired', true);
        }
        if(\SwVtTools\Patcher::isPatchApplied(vglobal('root_directory').'/modules/'.$request->get('module').'/patcher/patches/emaillog.patch', array('emaillog_1', 'emaillog_2')) == false) {
            $viewer->assign('EmailLogModificationRequired', true);
        } else {
            $sql = 'SELECT type, created, id FROM vtiger_tools_logs ORDER BY created DESC LIMIT 50';
            $result = $adb->query($sql);
            $logs = array();
            while($row = $adb->fetchByAssoc($result)) {
                $logs[$row['type']][] = $row;
            }
            $viewer->assign('Logs', $logs);
        }

        $availableBlocks = array();
        $sql = 'SELECT * FROM vtiger_tools_detailpart ORDER BY modulename';
        $result = $adb->query($sql);
        $detailviewTabs = array();
        while($row = $adb->fetchByAssoc($result)) {
            //$row['blocks'] = explode(',', $blocks);
            $detailviewTabs[] = $row;
            if(empty($availableBlocks[$row['modulename']])) {
                $moduleModel = Vtiger_Module_Model::getInstance($row['modulename']);
                $blocks = \Vtiger_Block::getAllForModule($moduleModel);
                $availableBlocks[$row['modulename']] = array();
                foreach($blocks as $block) {
                    $blockIndex[$row['modulename']][$block->id] = count($availableBlocks[$row['modulename']]);
                    $availableBlocks[$row['modulename']][] = array(
                        'id' => $block->id,
                        'text' => html_entity_decode(vtranslate($block->label, $row['modulename']))
                    );
                }

            }
        }
        $viewer->assign('availableBlocks', $availableBlocks);
        $viewer->assign('blockIndex', $blockIndex);
        $viewer->assign('detailviewTabs', $detailviewTabs);

        /** relation Tabs */
        $sql = 'SELECT * FROM vtiger_tools_reltab';
        $result = $adb->query($sql);
        $relTabs = array();
        $availableTabIndex = array();
        while($row = $adb->fetchByAssoc($result)) {
            $relTabs[$row['modulename']] = array(
                'id' => $row['id'],
                'modulename' => html_entity_decode($row['modulename']),
                'relations' => json_decode(html_entity_decode($row['relations']))
            );

            $availableTabs[$row['modulename']] = array(
                /*array(
                    'id' => 'tab-comments',
                    'text' => 'ModComments'
                ),
/*                array(
                    'id' => 'tab-history',
                    'text' => 'Updates'
                ),*/
            );
            $availableTabIndex[$row['modulename']] = array(
              //  'tab-comments' => 0,
              //  'tab-history' => 1,
            );

            $sql = 'SELECT * FROM vtiger_relatedlists WHERE tabid = ? ORDER BY sequence';
            $availableResult = $adb->pquery($sql, array(getTabid($row['modulename'])));

            while($available = $adb->fetchByAssoc($availableResult)) {

                $availableTabIndex[$row['modulename']]['rel-'.$available['relation_id']] = count($availableTabs[$row['modulename']]);

                $availableTabs[$row['modulename']][] = array(
                    'id' => 'rel-'.$available['relation_id'],
                    'text' => $available['label'].' ('.\SwVtTools\VtUtils::getModuleName($available['related_tabid']).')'
                );
            }

            $sql = 'SELECT id, title FROM vtiger_tools_detailpart WHERE modulename = "'.$row['modulename'].'" AND title != "_default"';
            $availableResult = $adb->query($sql);
            while($available = $adb->fetchByAssoc($availableResult)) {
                $availableTabIndex[$row['modulename']]['part-'.$available['id']] = count($availableTabs[$row['modulename']]);

                $availableTabs[$row['modulename']][] = array(
                    'id' => 'part-'.$available['id'],
                    'text' => $available['title'],
                );
            }
        }

        $moduleFields = array();
        foreach($loadModuleFields as $moduleName => $dmy) {
            $moduleFields[$moduleName] = \SwVtTools\VtUtils::getFieldsWithBlocksForModule($moduleName);
        }
        if(!empty($_REQUEST['tab'])) {
            $viewer->assign('current_tab', $_REQUEST['tab']);
        }
        $viewer->assign('availableTabIndex', $availableTabIndex);
        $viewer->assign('moduleFields', $moduleFields);
        $viewer->assign('availableTabs', $availableTabs);
        $viewer->assign('relTabs', $relTabs);

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
            "modules.Settings.$moduleName.views.resources.RedooUtils",
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
            "~/modules/Settings/$moduleName/views/resources/Backend.css",
            "~/modules/Settings/$moduleName/views/resources/pcss3t.css",
        );

        $cssScriptInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerStyleInstances = array_merge($headerScriptInstances, $cssScriptInstances);
        return $headerStyleInstances;
    }
}