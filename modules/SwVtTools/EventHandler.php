<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Stefan Warnat <support@stefanwarnat.de>
 * Date: 29.12.14 00:32
 * You must not use this file without permission.
 */

global $root_directory;
require_once($root_directory."/modules/SwVtTools/autoload_wf.php");

class SwVtToolsEventHandler extends VTEventHandler
{
    private static $DATA = array();

    public function handleEvent($handlerType, $entityData){

        switch($handlerType) {
            case 'vtiger.dispatch.before':
                return;
                require_once('modules/SwVtTools/filecache.php');

                $cacheFile = vglobal('root_directory') . '/modules/SwVtTools/cache/Module.php';

                if(!file_exists($cacheFile) || $_REQUEST['clearcache'] == '1') {
                    swtools_filecache(glob(vglobal('root_directory') . '/modules/*/models/Module.php'), $cacheFile);
                }
                require_once($cacheFile);
                break;
            case 'vtiger.process.customview.editajax.view.finish':
                if(empty(self::$DATA['recordId'])) {
                    return;
                }
                $content = ob_get_contents();
                ob_end_clean();

                $customViewModel = CustomView_Record_Model::getInstanceById(self::$DATA['recordId']);

                $blocks = \SwVtTools\VtUtils::getFieldsWithBlocksForModule(self::$DATA['moduleName']);
                $html = '<h4 class="filterHeaders">Filter Sort Order:</h4><br/>';
                $html .= '<div class="contentsBackground well">';
                $html .= '
                <table cellpadding="4" cellspacing="0" width="100%" border="0">
                  <tbody><tr>
                      <td class="dvtCellInfo" width="5%" align="right">Column:
                      </td>
                      <td class="dvtCellInfo" width="20%">

                      <select name="order_col" id="order_col"  class="chzn-select">
                          <option value="">'.getTranslatedString('LBL_NONE').'</option>';
                foreach($blocks as $blockLabel => $fields) {
                    $html .= '<optgroup label="'.$blockLabel.'">';
                    foreach($fields as $field) {
                        $html .= '<option '.($customViewModel->get('order_col') == $field->name ? 'selected="selected"' : '').' value="'.$field->name.'">'.$field->label.'</option>';
                    }
                    $html .= '</optgroup>';
                }

                $html .= '</select>
                    </td>
                                  <td class="dvtCellInfo" width="20%">
                                      <select name="order_dir" id="order_dir" class="small">
                                          <option value="ASC" '.($customViewModel->get('order_dir') == 'ASC' ? 'selected="selected"' : '').'>&uarr; ASC Ascending</option>
                                          <option value="DESC" '.($customViewModel->get('order_dir') == 'DESC' ? 'selected="selected"' : '').'>&darr; DESC Descending</option>
                                      </select>
                                  </td>
                                  <td class="dvtCellInfo" width="10%" align="right">numerische Sortierung:
                                  </td>
                                  <td class="dvtCellInfo" width="15%">
                                      <input type="checkbox" name="order_numeric_check" value="1" '.($customViewModel->get('order_numeric_check')>0?'checked="checked"':'').'>
                                      Skip Chars: <input type="text" alt="ignore the first X chars to get a numberic value" title="ignore the first X chars to get a numberic value" style="width:20px;" name="order_numeric" value="'.$customViewModel->get('order_numeric').'">
                                  </td></tr></table>';

                $html .= '</div>';
                $content  = str_replace('<div class="filterActions">', $html.'<div class="filterActions">', $content);
                echo $content;
                break;
        }
    }

    public function handleFilter($handlerType, $parameter) {
        $adb = \PearDatabase::getInstance();

        switch($handlerType) {
            case "vtiger.filter.process.customview.save.action.before":

                /**
                 * @var $request Vtiger_Request
                 */
                $request = $parameter[1];
                if(!empty($_POST['order_col'])) {
                    $sql = 'UPDATE vtiger_customview SET order_col=?, order_dir=?,order_numeric_check=?, order_numeric=? WHERE cvid=?';
                    $params = array($request->get('order_col'), $request->get('order_dir'), intval($request->get('order_numeric_check')),$request->get('order_numeric'), intval($request->get('record')));
                    $adb->pquery($sql, $params);

                }

                break;
            case 'vtiger.filter.detailview.relatedtabs':
                $detailViewModel = func_get_arg(2);
                $recordModel = $detailViewModel->getRecord();
                $moduleName = $recordModel->getModuleName();

                $sql = 'SELECT id, title FROM vtiger_tools_detailpart WHERE modulename = "'.$moduleName.'" AND title != "_default"';
                $result = $adb->query($sql);

                if($adb->num_rows($result) > 0) {
                    $detailViewIndex = false;
                    foreach ($parameter as $index => $data) {
                        if ($data['linkKey'] === 'LBL_RECORD_DETAILS') {
                            $detailViewIndex = $index;
                            break;
                        }
                    }

                    if ($detailViewIndex !== false) {
                        $counter = 1;
                        while ($row = $adb->fetchByAssoc($result)) {
                            $tmp = array(
                                'linktype' => 'DETAILVIEWTAB',
                                'linklabel' => $row['title'],
                                'linkurl' => $recordModel->getDetailViewUrl() . '&mode=showDetailViewByMode&requestMode=full&ptabid=' . $row['id'],
                                'linkicon' => 'icon-volume-down'
                            );

                            array_splice($parameter, $detailViewIndex + $counter, 0, array($tmp));
                            $counter++;
                        }

                    }
                }

                $sql = 'SELECT relations FROM vtiger_tools_reltab WHERE modulename = "'.$moduleName.'"';
                $result = $adb->query($sql);

                if($adb->num_rows($result) > 0) {
                    $relations = json_decode(html_entity_decode($adb->query_result($result, 0, 'relations')), true);
                    $labels = array_flip($relations['labels']);

                    $new = array(
                        'first' => array(),
                        'tabs' => array()
                    );
                    //var_dump($labels);
                    //var_dump($parameter);
                    foreach($parameter as $reltab) {
                        if(empty($reltab['relatedModuleName']) && $reltab['linklabel'] != 'LBL_UPDATES') {
                            $new['first'][] = $reltab;
                        } else {
                            //var_dump($reltab['linklabel'], $labels[$reltab['linklabel']]);
                            if(isset($labels[$reltab['linklabel']]) || $reltab['linklabel'] == 'LBL_UPDATES') {
                                if($reltab['linklabel'] != 'LBL_UPDATES') $reltab['linktype'] = 'DETAILVIEWRELATED';
                                $new['tabs'][$labels[$reltab['linklabel']]] = $reltab;
                            }
                        }
                    }

                    ksort($new['tabs']);

                    $parameter = array_merge($new['first'], $new['tabs']);

                }

                break;
            case 'vtiger.filter.detailview.blocks.sql':
                if(empty($_REQUEST['mode']) || $_REQUEST['mode'] != 'showDetailViewByMode') return $parameter;
                /**
                 * @var $moduleInstance Vtiger_Module
                 */
                $moduleInstance = func_get_arg(2);

                if(empty($_REQUEST['ptabid'])) {
                    $sql = 'SELECT blockids FROM vtiger_tools_detailpart WHERE modulename = "'.$moduleInstance->name.'" AND title = "_default"';
                } else {
                    $ptabid = intval($_REQUEST['ptabid']);
                    $sql = 'SELECT blockids FROM vtiger_tools_detailpart WHERE id = "'.$ptabid.'" AND modulename = "'.$moduleInstance->name.'"';
                }
                $result = $adb->query($sql);
                if($adb->num_rows($result) == 0) return $parameter;
                $blockids = $adb->query_result($result, 0, 'blockids');
                $blockids = preg_replace('/[^0-9,]/', '', $blockids);

                $parts = explode('ORDER BY', $parameter);
                $parameter = $parts[0]. ' AND blockid IN ('.$blockids.') ORDER BY FIELD (blockid, '.$blockids.'), sequence';
                break;
            case "vtiger.filter.searchrecords.query":
                if(empty($_REQUEST['action']) || $_REQUEST['action'] != 'BasicAjax') return $parameter;

                $searchValue = $_REQUEST['search_value'];
                $srcModule = $_REQUEST['module'];
                $targetModule = $_REQUEST['search_module'];
                $field = $_REQUEST['src_field'];

                $sql = 'SELECT `condition` FROM vtiger_tools_referencefilter WHERE modulename = ? AND field = ? AND tomodule = ?';

                $result = $adb->pquery($sql, array($srcModule, $field, $targetModule));

                if($adb->num_rows($result) == 0) return $parameter;
                $data = $adb->fetchByAssoc($result);

                $conditions = json_decode(html_entity_decode($data['condition']), true);

                $where = array();
                $where[] = 'vtiger_crmentity.label LIKE "%'.$searchValue.'%"';

                foreach($conditions as $condition) {
                    switch($condition['operator']) {
                        case 'e':
                            $where[] = '`'.$condition['column'].'` = ?';
                            $params[] = $condition['value'];
                            break;
                        case 'n':
                            $where[] = '`'.$condition['column'].'` <> ?';
                            $params[] = $condition['value'];
                            break;
                        case 's':
                            $where[] = '`'.$condition['column'].'` LIKE ?';
                            $params[] = $condition['value'] . '%';
                            break;
                        case 'ew':
                            $where[] = '`'.$condition['column'].'` LIKE ?';
                            $params[] = '%'.$condition['value'];

                            break;
                        case 'c':
                            $where[] = '`'.$condition['column'].'` LIKE ?';
                            $params[] = '%'.$condition['value'] . '%';
                            break;
                        case 'k':
                            $where[] = '`'.$condition['column'].'` NOT LIKE ?';
                            $params[] = '%' . $condition['value'] . '%';
                            break;
                        case 'l':
                            $where[] = '`'.$condition['column'].'` < ?';
                            $params[] = $condition['value'];
                            break;
                        case 'g':
                            $where[] = '`'.$condition['column'].'` > ?';
                            $params[] = $condition['value'];
                            break;
                        case 'm':
                            $where[] = '`'.$condition['column'].'` "<= ?';
                            $params[] = $condition['value'];
                            break;
                        case 'h':
                            $where[] = '`'.$condition['column'].'` >= ?';
                            $params[] = $condition['value'];
                            break;
                        case 'a':
                            $where[] = '`'.$condition['column'].'` > ?';
                            $params[] = $condition['value'];
                            break;
                        case 'b':
                            $where[] = '`'.$condition['column'].'` < ?';
                            $params[] = $condition['value'];
                            break;
                    }
                }

                $sqlQuery = 'SELECT `label`, `crmid`, `setype`, `createdtime` '.\SwVtTools\VtUtils::getModuleTableSQL($targetModule).' WHERE  vtiger_crmentity.deleted = 0 AND '.implode(' AND ', $where);
                return $adb->pquery($sqlQuery, $params);
                break;
            case "vtiger.filter.listview.querygenerator.before":
                if($_REQUEST['view'] != 'Popup') return $parameter;
                /**
                 * @var $queryGenerator QueryGenerator
                 */
                $queryGenerator = $parameter;

                $srcModule = $_REQUEST['src_module'];
                $targetModule = $_REQUEST['module'];
                $field = $_REQUEST['src_field'];

                $sql = 'SELECT `condition` FROM vtiger_tools_referencefilter WHERE modulename = ? AND field = ? AND tomodule = ?';
                $result = $adb->pquery($sql, array($srcModule, $field, $targetModule));

                if($adb->num_rows($result) == 0) return $parameter;
                $data = $adb->fetchByAssoc($result);
                $conditions = json_decode(html_entity_decode($data['condition']), true);

                foreach($conditions as $condition) {
                    $queryGenerator->addUserSearchConditions(array('search_field' => $condition['field'], 'search_text' => $condition['value'], 'operator' => $condition['operator']));
                }

                return $queryGenerator;
                break;
            case "vtiger.filter.listview.orderby":
                /**
                 * @var $queryGenerator QueryGenerator
                 */
                $queryGenerator = func_get_arg(2);
                $viewId = ListViewSession::getCurrentView($queryGenerator->getModule());

                if(!empty($viewId)) {
                    $sql = 'SELECT order_col, order_dir, order_numeric_check, order_numeric FROM vtiger_customview WHERE cvid = ?';
                    $result = $adb->pquery($sql, array($viewId));
                    if($adb->num_rows($result) > 0) {
                        $data = $adb->fetchByAssoc($result);
                        if(!empty($data['order_col'])) {
                            $parameter[0] = $data['order_col'];
                            $parameter[1] = $data['order_dir'];
                        }
                    }
                }
                break;
            case "vtiger.filter.process.customview.editajax.view.before":
                $recordId = $parameter[1]->get('record');

                self::$DATA['recordId'] = $recordId;
                self::$DATA['moduleName'] = $parameter[1]->get('source_module');
                ob_start();

                break;
        }

        return $parameter;
    }

}
