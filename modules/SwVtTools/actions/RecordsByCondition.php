<?php
use \Workflow\VTEntity;
use \Workflow\VTTemplate;

global $root_directory;
require_once($root_directory."/modules/SwVtTools/autoload_wf.php");

class SwVtTools_RecordsByCondition_Action extends Vtiger_Action_Controller {

    function checkPermission(Vtiger_Request $request) {
        return true;
    }

    public function process(Vtiger_Request $request) {
        $adb = \PearDatabase::getInstance();

        $module = $request->get('recordmodule');

        $sqlQuery = "SELECT vtiger_crmentity.crmid, label FROM vtiger_crmentity WHERE setype = ? AND vtiger_crmentity.label LIKE ? AND deleted = 0";
        $result = $adb->pquery($sqlQuery, array($request->get('recordmodule'), '%'.$request->get('query').'%'));

        while($row = $adb->fetchByAssoc($result)) {
            $return['results'][] = array(
                'text' => $row['label'],
                'id' => $row['crmid']
            );

        }
        /**

        $return = array();
        foreach($products['Products'] as $result) {
            //var_dump($result);
            $return['results'][] = array(
                'group' => 'Products',
                'text' => $result->get('label'),
                'id' => $result->getId()
            );
        }
        foreach($services['Services'] as $result) {
            $return['results'][] = array(
                'group' => 'Services',
                'text' => $result->get('label'),
                'id' => $result->getId()
            );
        }*/

        echo json_encode($return);
        exit();
    }
    public function validateRequest(Vtiger_Request $request) {
        $request->validateReadAccess();
    }
}

?>