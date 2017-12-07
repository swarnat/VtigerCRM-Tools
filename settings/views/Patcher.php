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


class Settings_SwVtTools_Patcher_View extends Settings_Vtiger_Index_View {

    public function process(Vtiger_Request $request) {
        $adb = \PearDatabase::getInstance();

        global $YetiForce_current_version, $vtiger_current_version;

        if(!empty($_FILES['patchfile'])) {
            $patchfile = $_FILES['patchfile']['tmp_name'];
            if(filesize($patchfile) < 10) {
                throw new \Exception('Patch could not be uploaded!');
            }
            $hash = md5(microtime(false).rand(100000, 999999));

            move_uploaded_file($patchfile, vglobal('root_directory').'/modules/'.$request->get('module').'/patcher/patches/'.$hash.'.patch');

        } elseif(!empty($_REQUEST['hash'])) {
            $hash = $_REQUEST['hash'];
        } else {
            throw new \Exception('Please firstly upload a patch file!');
        }

        $patchfile = vglobal('root_directory').'/modules/'.$request->get('module').'/patcher/patches/'.$hash.'.patch';

        global $current_user;
        ini_set('display_errors', 1);error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED & ~E_NOTICE);

        $step = 1;
        if(!empty($_GET['step'])) {
            $step = intval($_GET['step']);
        }

        echo '<div style="margin:10px;padding:10px;">';
        echo '<h2>Vtiger File Patcher by Stefan Warnat</h2><br/>';

        $className = '\\'.basename(dirname(dirname(__FILE__))).'\\Patcher';

        $Patcher = new $className();
        $Patcher->setBackupFolder(vglobal('root_directory').DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$request->get('module').DIRECTORY_SEPARATOR.'patcher'.DIRECTORY_SEPARATOR.'backup'.DIRECTORY_SEPARATOR);
        $Patcher->setHash($hash);

        if($request->has('remove') && $request->get('remove') == '1') {
            $Patcher->removePatchMode();
        }

        if($step == 1) {
            echo '<h3>Step 1 / 2  - Dry run to check compatibility</h3><br/>';
            $return = $Patcher->applyPatchFile($patchfile, vglobal('root_directory').'/', true);

            if($return['success'] == false) {
                echo '<div><span style="color:red;"><strong>Errors during dry run! No files are modified!</strong></span></div>';

                if(count($return['errors']) > 0) {
                    foreach($return['errors'] as $error) {
                        echo '<div><span style="color:red;">ERROR</span> '.$error.'</div>';
                    }
                }

                echo '<strong>Please resolve problems before you could continue!</strong>';
                echo '&nbsp;&nbsp;<a class="btn" href="index.php?parent=Settings&module='.$request->get('module').'&view='.$request->get('view').'&hash='.$return['hash'].'&viewpatch=1'.($request->get('remove') == '1'?'&remove=1':'').'">View file manipulations manually to check</a>';

            } else {
                foreach($return['messages'] as $msg) {
                    echo '<div><span style="color:darkgreen;"><strong>SUCCESS</strong></span> '.$msg.'</div>';
                }

                echo '<br/><strong>In case of problems after patch call this url to restore all files:</strong> <a href="modules/'.$request->get('module').'/repair.php?ts='.$return['hash'].'">modules/'.$request->get('module').'/repair.php?ts='.$return['hash'].'</a><br/>';

                echo '<br/><a class="btn btn-success" href="index.php?parent=Settings&module='.$request->get('module').'&view='.$request->get('view').'&step=2&hash='.$return['hash'].''.($request->get('remove') == '1'?'&remove=1':'').'">GO - apply modifications to files</a>';

                echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="font-style:italic;">(Backups of original files will be stored)</span><br/><br/>';

                echo '<a class="btn btn-default" href="index.php?parent=Settings&module='.$request->get('module').'&view='.$request->get('view').'&hash='.$hash.'&viewpatch=1'.($request->get('remove') == '1'?'&remove=1':'').'">View file manipulations manually to check</a>';
            }

            if($request->get('viewpatch') == '1') {
                echo '<br/><div style="padding:10px;border:1px solid #000;margin:5px;">';
                echo $Patcher->generateManipulationsView($patchfile, rtrim(vglobal('root_directory'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR);
                echo '</div>';
            }
        } elseif($step == 2) {
            echo '<h3>Step 2 / 2 </h3><br/>';
            $hash = $request->get('hash');
            $Patcher->setHash(preg_replace('/[^a-zA-Z0-9]/', '', $hash));

            $return = $Patcher->applyPatchFile($patchfile, vglobal('root_directory').'/', false);

            if(count($return['errors']) > 0) {
                foreach($return['errors'] as $error) {
                    echo '<div><span style="color:red;"><strong>ERROR</strong></span> '.$error.'</div>';
                }
            }

            foreach($return['messages'] as $msg) {
                echo '<div><span style="color:darkgreen;"><strong>SUCCESS</strong></span> '.$msg.'</div>';
            }

            echo '<br/><h3 style="color:darkgreen;">Successfully applied!</h3>';

            echo '<br/><strong>In case of problems after patch, call this url to quickly restore all files:</strong> <a href="modules/'.$request->get('module').'/repair.php?ts='.$return['hash'].'">modules/'.$request->get('module').'/repair.php?ts='.$return['hash'].'</a><br/>';
            echo '<br/><br/><a class="btn btn-success" href="index.php?module=='.$request->get('module').'&parent=Settings&view=Index">Back to Main page</a>';
        }

        echo '</div>';

    }


}

