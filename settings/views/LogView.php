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

class Settings_SwVtTools_LogView_View extends Settings_Vtiger_Index_View {

    public function preProcess (Vtiger_Request $request) {

    }
    public function process(Vtiger_Request $request) {
        $adb = \PearDatabase::getInstance();

        $viewer = $this->getViewer($request);

        $logId = intval($request->get('lid'));

        $sql = 'SELECT * FROM vtiger_tools_logs WHERE id = ?';
        $result = $adb->pquery($sql, array($logId));

        $data = $adb->fetchByAssoc($result);

        echo '<h1>Log created at '.$data['created'].'</h1>';
        echo '<pre>'.$data['log'].'</pre>';
        exit();
    }


}

