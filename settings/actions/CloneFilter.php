<?php
global $root_directory;
require_once($root_directory."/modules/SwVtTools/autoload_wf.php");

class SwVtTools_GCalSelection_Action extends Vtiger_Action_Controller {

    public function process(Vtiger_Request $request) {
        global $current_user;

        $adb = PearDatabase::getInstance();

        $filterId = $request->get('filterId');

        $sql = "SELECT * FROM vtiger_customview WHERE cvid = ?";
        $result = $adb->pquery($sql, array($filterId));
        $row = $adb->fetchByAssoc($result);

        $data = \SwVtTools\CustomView::export($filterId);

        $newcvid = \SwVtTools\CustomView::import($data, $row["viewname"]." Copy");

    }


}

