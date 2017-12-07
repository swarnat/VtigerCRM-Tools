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

class Settings_SwVtTools_GermanDateFormat_View extends Settings_Vtiger_Index_View {

    public function process(Vtiger_Request $request) {
        $adb = \PearDatabase::getInstance();

        $viewer = $this->getViewer($request);

        $qualifiedModuleName = $request->getModule(false);
        $viewer->view('GermanDateFormat.tpl', $qualifiedModuleName);
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