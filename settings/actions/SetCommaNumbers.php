<?php

global $root_directory;
require_once($root_directory."/modules/SwVtTools/autoload_wf.php");

class Settings_SwVtTools_SetCommaNumbers_Action extends Settings_Vtiger_Basic_Action {

    function checkPermission(Vtiger_Request $request) {
   		return true;
   	}

    public function process(Vtiger_Request $request) {
        $adb = PearDatabase::getInstance();
        //$params = $request->getAll();

        $value = $request->get('value');

        $moduleModel = Vtiger_Module_Model::getInstance("SwVtTools");
        require_once('vtlib/Vtiger/Module.php');
        $link_module = Vtiger_Module::getInstance("SwVtTools");

        if($value == 'true') {
            $link_module->addLink('HEADERSCRIPT','ToolsGermanNumbers',"modules/SwVtTools/resources/germannumbers.js?v1=".$moduleModel->version);
        } elseif($value == 'false') {
            $link_module->deleteLink('HEADERSCRIPT','ToolsGermanNumbers');
        }

    }
}