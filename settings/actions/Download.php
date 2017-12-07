<?php
global $root_directory;
require_once($root_directory."/modules/SwVtTools/autoload_wf.php");

class Settings_SWVtTools_Download_Action extends Settings_Vtiger_Basic_Action {

    private function addDirectoryToZip(&$zip, $dir, $base = 0) {
        foreach(glob($dir . '/*') as $file) {
            if(is_dir($file)) {
                $this->addDirectoryToZip($zip, $file, $base);
            } else {
                $zip->addFile($file, substr($file, $base));
            }
        }
    }

    public function process(Vtiger_Request $request) {
        global $current_user;

        $module = $request->get('module_name');

        $zip = new \ZipArchive();
        $zipfilename = vglobal('root_directory')."/test/".$module."_" . date('Y-m-d') . ".zip";
        $ret = $zip->open($zipfilename, \ZipArchive::CREATE);
        if(!is_writable(vglobal('root_directory')."/test/")) {
            echo 'test folder not writable';
            exit();
        }
        $path = vglobal('root_directory') . '/modules/'.$module.'';

        /*$this->addDirectoryToZip(
            $zip, // the ZipArchive object sent by refference
            $path, // The path to the folder you wish to archive
            strlen($path) + 1 // The string length of the base folder
        );*/
        $path = vglobal('root_directory') . '/modules/'.$module.'';
        $this->addDirectoryToZip(
            $zip, // the ZipArchive object sent by refference
            $path, // The path to the folder you wish to archive
            strlen(vglobal('root_directory')) + 1 // The string length of the base folder
        );
        $path = vglobal('root_directory') . '/modules/Settings/'.$module.'';
        $this->addDirectoryToZip(
            $zip, // the ZipArchive object sent by refference
            $path, // The path to the folder you wish to archive
            strlen(vglobal('root_directory')) + 1 // The string length of the base folder
        );

        $zip->close();

        header("Content-Description: File Transfer");
        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"".$module.".zip\"");
        readfile($zipfilename);
        @unlink($zipfilename);
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

