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

use \SwVtTools\Patcher;

class Settings_SwVtTools_ReferenceFilterEditor_View extends Settings_Vtiger_Index_View {

    public function process(Vtiger_Request $request) {
        $adb = \PearDatabase::getInstance();

        $viewer = $this->getViewer($request);

        $sql = 'SELECT * FROM vtiger_tools_referencefilter WHERE id = ?';
        $result = $adb->pquery($sql, array(intval($request->get('id'))));
        $data = $adb->fetchByAssoc($result);

        $condition = json_decode(html_entity_decode($data['condition']), true);
        $target = array();

        foreach($condition as $row) {
            $target[] = $row['field'].';'.$row['operator'].';'.$row['value'];
        }

        echo implode(PHP_EOL, $target);
    }

}

