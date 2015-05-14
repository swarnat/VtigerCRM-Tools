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
//                    var_dump($adb->convert2Sql($sql, $params));
                }

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
//                var_dump($queryGenerator);
//                $queryGenerator->
//                var_dump($queryGenerator);
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
