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

class SwVtTools_SidebarWidget_View extends Vtiger_BasicAjax_View {

    public function process(Vtiger_Request $request) {
        $current_user = $cu_model = Users_Record_Model::getCurrentUserModel();
        $currentLanguage = Vtiger_Language_Handler::getLanguage();

        $adb = PearDatabase::getInstance();
        $viewer = $this->getViewer($request);
        $module = $request->get('source_module');
        $crmid = (int)$request->get('record');
        $tabid = getTabid($module);

        $sidebar_id = intval($_REQUEST["sID"]);

       $sql = "SELECT content FROM vtiger_tools_sidebar WHERE id = ".intval($sidebar_id)." AND tabid = ".$tabid;
       $result = $adb->query($sql);

       $sidebar = $adb->fetchByAssoc($result);

        \SwVtTools\VTEntity::setUser($current_user);
       $context = \SwVtTools\VTEntity::getForId($crmid, $_POST["return_module"]);
       $content = \SwVtTools\VTTemplate::parse(html_entity_decode($sidebar["content"], ENT_QUOTES, 'UTF-8'), $context);

       echo $content;

    }
}