<?php
global $root_directory;
require_once($root_directory."/modules/SwVtTools/autoload_wf.php");

class Settings_SWVtTools_DBSchema_Action extends Settings_Vtiger_Basic_Action {

    public function process(Vtiger_Request $request) {
        global $current_user;

        $adb = PearDatabase::getInstance();

        $tables = $adb->get_tables();

        echo '<pre>';
        foreach($tables as $table) {
            echo '# Table: '.$table."\n";
            $result = $adb->query("SHOW COLUMNS FROM `".$table."`");
            while($col = $adb->fetchByAssoc($result)) {
                echo $table.';'.$col['field'].';'.$col['type']."\n";
            }
        }
    }
    public function validateRequest(Vtiger_Request $request) {
        $request->validateReadAccess();
    }

}

