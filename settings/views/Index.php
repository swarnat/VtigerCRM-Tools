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

	public function process(Vtiger_Request $request) {
        $adb = \PearDatabase::getInstance();

        if(!empty($_POST['tool_action'])) {
            switch($_POST['tool_action']) {
                case 'createRelation':
                    include_once('vtlib/Vtiger/Module.php');

                    $fromInstance = Vtiger_Module::getInstance(\SwVtTools\VtUtils::getModuleName($_POST['tabid']));
                    $toModuleName = \SwVtTools\VtUtils::getModuleName($_POST['related_tabid']);
                    $toInstance = Vtiger_Module::getInstance($toModuleName);

                    $fromInstance->setRelatedlist($toInstance,$_POST['label'],array('add','select'), $toModuleName=='Documents'?'get_attachments':'get_dependents_list');
                    echo '<div class="alert alert-success" style="padding:10px;">Relation was created</div>';
                    break;
            }
        }
        $moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$viewer = $this->getViewer($request);

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
			"modules.Settings.$moduleName.views.resources.backend"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);

        return $headerScriptInstances;
	}
    function getHeaderCss(Vtiger_Request $request) {
        $headerScriptInstances = parent::getHeaderCss($request);
        $moduleName = $request->getModule();

        $cssFileNames = array(
            //"~/modules/Settings/$moduleName/resources/Colorizer.css"
        );

        $cssScriptInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerStyleInstances = array_merge($headerScriptInstances, $cssScriptInstances);
        return $headerStyleInstances;
    }
}