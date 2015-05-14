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
require_once($root_directory."/modules/SwVtTools/autoloader.php");

class Settings_SwVtTools_Example_View extends Settings_Vtiger_Index_View {

	public function process(Vtiger_Request $request) {
        include_once 'include/Webservices/Retrieve.php';
        include_once 'include/Webservices/Revise.php';

        $user = CRMEntity::getInstance('Users');
        $user->id=1;
        $user->retrieve_entity_info($user->id, 'Users');

        $wsrecord = vtws_retrieve(vtws_getWebserviceEntityId('Invoice', 159), $user);

        $new['LineItems'] = $wsrecord['LineItems'];
        $new['id'] = $wsrecord['id'];

        $new['LineItems'][] = array(
            'parent_id' => '7x159',
            'productid' => '14x109',
            'sequence_no' => '2',
            'quantity' => '3',
            'listprice' => '2.1',
            'comment' => 'Kommentar '.date('Y-m-d H:i:s'),
            'incrementondel' => '1',
            'tax1' => 5
        );
        $lead = vtws_revise($new, $user);

        echo '<pre>';
        var_dump($wsrecord);
        echo '</pre>';

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