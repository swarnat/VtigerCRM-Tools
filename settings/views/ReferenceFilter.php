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

class Settings_SwVtTools_ReferenceFilter_View extends Settings_Vtiger_Index_View {

    public function process(Vtiger_Request $request) {
        $adb = \PearDatabase::getInstance();

        $viewer = $this->getViewer($request);

        $sql = 'SELECT * FROM vtiger_tools_referencefilter ORDER BY modulename, field';
        $result = $adb->query($sql);
        $filter = array();
        while($row = $adb->fetchByAssoc($result)) {
            $row['field'] = SwVtTools\VtUtils::getFieldInfo($row['field'], getTabid($row['modulename']));
            $row['condition'] = array();
            foreach($row['condition'] as $condition) {
                $row['condition'][] = $condition['field'].' is equal '.$condition['opterator'];
            }
            $row['condition'] = implode(PHP_EOL, $row['condition']);
            $filter[] = $row;
        }

        $viewer->assign('referencefilter', $filter);

        $qualifiedModuleName = $request->getModule(false);
        $viewer->view('ReferenceFilter.tpl', $qualifiedModuleName);
    }


}

