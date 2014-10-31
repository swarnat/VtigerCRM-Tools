<?php
global $root_directory;
require_once($root_directory."/modules/SwVtTools/autoload_wf.php");

class Settings_SWVtTools_DBSchemaCheck_Action extends Settings_Vtiger_Basic_Action {

    public function process(Vtiger_Request $request) {
        global $current_user;

        if($request->get('schema') != '') {
            $schema = $request->get('schema');
            $schema = explode("\n", trim($schema));
            foreach($schema as $line) {
                if(substr($line, 0, 1) == '#') {
                    continue;
                }
                $parts = explode(';', $line);
                $this->checkCol($parts[0],$parts[1],$parts[2], false, false);
            }
        }

        echo '<form method="POST" action="#"><textarea name="schema" style="width:1000px;height:800px;">'.$request->get('schema').'</textarea><br/><input type="submit" value="Submit"> </form>';
    }

    public function checkCol($table, $colum, $type, $create = false, $resetType = false) {
        global $adb;

        $result = $adb->query("SHOW COLUMNS FROM `".$table."` LIKE '".$colum."'");
        $exists = ($adb->num_rows($result))?true:false;

        if($exists == false) {
            if($create == true) {
                echo "Add column '".$table."'.'".$colum."'<br>";
                $adb->query("ALTER TABLE `".$table."` ADD `".$colum."` ".$type." NOT NULL".($default !== false?" DEFAULT  '".$default."'":""), false);
            } else {
                echo "# Not Exist! column '".$table."'.'".$colum."' (".$type.")<br>";
            }
        } elseif($resetType == true) {
            $existingType = strtolower(html_entity_decode($adb->query_result($result, 0, 'type'), ENT_QUOTES));
            $existingType = str_replace(' ', '', $existingType);
            if($existingType != strtolower(str_replace(' ', '', $type))) {
                $sql = "ALTER TABLE  `".$table."` CHANGE  `".$colum."`  `".$colum."` ".$type.";";
                $adb->query($sql);
            }
        }

        return $exists;
    }
    public function validateRequest(Vtiger_Request $request) {
        $request->validateReadAccess();
    }

}

