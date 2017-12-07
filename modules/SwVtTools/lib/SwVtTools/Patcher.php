<?php
/**
 * Patcher Class to automatically Apply Patches
 *
 * @version 1.3
 *
 * Changelog
 * 1.3 - 2017-12-07
 *      Implement Duplicate ModificationID check
 *      Implement XML Error output
 * 1.2 - 2017-05-27
 * 		Remove Patch function
 * 1.1 - 2017-04-09
 * 		Add isPatchApplied function
 * 		Insert ReplaceWith Operation
 * 		Add flexible number of spaces in line before content
 */
namespace SwVtTools;

class Patcher
{
    private $hash = '';
    private $backupFolder = null;
    private $messages = array();
    private $basePath = null;
    private $_OnlyRemove = false;

    public function setHash($hash) {
        $this->hash = $hash;
    }
    public function setBackupFolder($folder) {
        if(!is_writeable($folder)) {
            throw new \Exception('Backup folder not writable!');
        }

        $this->backupFolder = $folder;
    }
    public function removePatchMode() {
        $this->_OnlyRemove = true;
    }
    public static function isPatchApplied($patchFilename, $patchIds = array()) {
        if(!is_array($patchIds)) $patchIds = array($patchIds);

        $array = XML2Array::createArray(file_get_contents($patchFilename));
        $manipulations = $array['SWPatcher']['patch'];
        if(!isset($manipulations[0]) && isset($manipulations['id'])) {
            $manipulations = array($manipulations);
        }

        $patchIds = array_flip($patchIds);
        foreach($manipulations as $modification) {
            if(!isset($patchIds[$modification['id']])) continue;

            $filename = vglobal('root_directory').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $modification['file']);
            $content = file_get_contents($filename);

            if(strpos($content, 'SWPATCHER-'.strtoupper(md5($modification['id'])).'-START') !== false) {

                unset($patchIds[$modification['id']]);
            }
        }

        return empty($patchIds);
    }

    public function generateManipulationsView($patchFilename, $basePath) {
        if(!is_readable($patchFilename)) {
            throw new \Exception('Patch file not readable!');
        }

        $files = $manipulations = $errors = array();

        $array = XML2Array::createArray(file_get_contents($patchFilename));
        $manipulations = $array['SWPatcher']['patch'];
        if(!isset($manipulations[0]) && isset($manipulations['id'])) {
            $manipulations = array($manipulations);
        }

        foreach($manipulations as $modification) {
            //if($modification['file'] != 'modules/Vtiger/models/Field.php') continue;

            $modification['orig_search'] = $modification['search'];
            $modification['search'] = explode("[//]", $modification['search']);
            foreach($modification['search'] as $index => $value) {
                $modification['search'][$index] = trim($value);
            }
            $modification['search'] = implode("<br/>", $modification['search']);

            $modification['file'] = str_replace('/', DIRECTORY_SEPARATOR, $modification['file']);

            $files[$modification['file']][] = $modification;
        }

        $filenames = array_keys($files);
        sort($filenames);

        $html = '<strong>All filepaths are related to '.$basePath.'</strong><br/>';
        $counter = 1;
        foreach($filenames as $file) {
            $html .= '<br/><h3>Open File: '.$file.'</h3>';
            $html .= '<div style="padding:5px 10px;">';

            if($this->_OnlyRemove == false) {
                foreach ($files[$file] as $mod) {
                    $html .= '<div class="swpatcher_startmod" style="margin-top:10px;padding:5px 10px;background-color:#6699cc;color:#ffffff;border-top:1px solid #aaa;font-size:15px;"><strong>' . $counter++ . '. Start Modification <em>' . $mod['id'] . '</em></strong></div>';
                    if(!empty($mod['optional'])) {
                        $html .= '<em>This modification is optional and is not required for module feature. No problem if it don\'t exist in your system.</em>';
                    }

                    $html .= '<div style="margin-left:10px;">';
                    $mod['search'] = str_replace(array("\r", "\n"), '', $mod['search']);
                    $html .= '<strong>Search:</strong><br/>';
                    $html .= '<div class="swpatcher_search" style="margin:4px 10px;font-family:\'Courier New\';padding-left:  5px; border-left:3px solid #eee;">' . $mod['search'] . '</div>';

                    switch (strtolower($mod['method'])) {
                        case 'insertbefore':
                            $html .= '<strong>insert before:</strong>';
                            break;
                        case 'insertafter':
                            $html .= '<strong>insert after:</strong>';
                            break;
                        case 'replacewith':
                            $html .= '<strong>replace with:</strong>';
                            break;
                    }

                    $mod['modification'] = $this->getModificationContent($mod);

                    $html .= '<div class="swpatcher_search" style="margin:4px 10px;white-space:pre;font-family:\'Courier New\';padding-left:  5px; border-left:3px solid #eee;">' . $mod['modification'] . '</div>';
                    $html .= '</div>';
                }
            } else {
                foreach ($files[$file] as $mod) {
                    $html .= '<div class="swpatcher_startmod" style="margin-top:10px;padding:5px 0;background-color:#6699cc;color:#ffffff;border-top:1px solid #aaa;font-size:15px;"><strong>' . $counter++ . '. Start Modification <em>' . $mod['id'] . '</em></strong></div>';
                    if(!empty($mod['optional'])) {
                        $html .= '<em>This modification is optional and is not required for module feature. No problem if it don\'t exist in your system.</em>';
                    }

                    $html .= '<div style="margin-left:10px;">';
                    $mod['search'] = str_replace(array("\r", "\n"), '', $mod['search']);
                    $html .= '<strong>Search:</strong><br/>';

                    //$modification = $this->getModificationContent($mod, false);

                    $html .= '<div class="swpatcher_search" style="margin:4px 10px;font-family:\'Courier New\';padding-left:  5px; border-left:3px solid #eee;">' . '/**SWPATCHER-'.strtoupper(md5($mod['id'])).'-START-'.$this->hash.'**/' . '</div>';

                    switch (strtolower($mod['method'])) {
                        case 'insertbefore':
                        case 'insertafter':
                            $html .= '<strong>Delete until</strong>';

                            $html .= '<div class="swpatcher_search" style="margin:4px 10px;white-space:pre;font-family:\'Courier New\';padding-left:  5px; border-left:3px solid #eee;">' . '/**SWPATCHER-'.strtoupper(md5($mod['id'])).'-FINISH**/' . '</div>';
                            break;
                        case 'replacewith':
                            $html .= '<strong>Remove until:</strong>';
                            $untilStr = $mod['search'];

                            $html .= '<div class="swpatcher_search" style="margin:4px 10px;white-space:pre;font-family:\'Courier New\';padding-left:  5px; border-left:3px solid #eee;">' . '/**SWPATCHER-'.strtoupper(md5($mod['id'])).'-FINISH**/' . '</div>';

                            $html .= '<strong>Replace with:</strong>';
                            $html .= '<div class="swpatcher_search" style="margin:4px 10px;white-space:pre;font-family:\'Courier New\';padding-left:  5px; border-left:3px solid #eee;">' . $untilStr . '</div>';
                            break;
                    }


                    $html .= '</div>';
                }
            }

            $html .= '</div>';
        }

        return $html;
    }

    private function getModificationContent($modification, $asArray = false) {
        $head = array(
            '/**SWPATCHER-'.strtoupper(md5($modification['id'])).'-START-'.$this->hash.'**/',
            '/** Don\'t remove the Start and Finish Markup! Modified: '.date('Y-m-d H:i:s').' by '.__NAMESPACE__.' **/',
        );
        $foot = array(
            '/**SWPATCHER-'.strtoupper(md5($modification['id'])).'-FINISH**/',
        );

        if($asArray === true) {
            $return = array_merge($head, $modification['modification']);
        } else {
            $return = implode(PHP_EOL, $head).PHP_EOL.$modification['modification'].PHP_EOL;
        }

        switch(strtolower($modification['method'])) {
            case 'replacewith':
                $parts = explode("[//]", $modification['orig_search']);
                $modification['modification'] .= PHP_EOL;
                foreach($parts as $line) {
                    if($asArray === true) {
                        $return[] = '//REPLACED-' . strtoupper(md5($modification['id'])) . '// ' . trim($line);
                    } else {
                        $return .= '//REPLACED-' . strtoupper(md5($modification['id'])) . '// ' . trim($line).PHP_EOL;
                    }
                }

                break;
        }

        if($asArray === true) {
            $return = array_merge($return, $foot);
        } else {
            $return .= implode(PHP_EOL, $foot);
        }

        return $return;
    }

    public function applyPatchFile($patchFilename, $basePath, $dryRun = true) {
        $this->basePath = $basePath;

        if(empty($this->hash)) {
            $this->hash = md5(microtime(true).mt_rand(100000, 999999));
        }

        if(!is_readable($patchFilename)) {
            throw new \Exception('Patch file not readable!');
        }

        $files = $manipulations = $errors = array();

        if($dryRun === true) {
            $this->messages[] = 'DRYRUN - No files will be modified!';
        }

        $array = XML2Array::createArray(file_get_contents($patchFilename));
        $manipulations = $array['SWPatcher']['patch'];
        if(!isset($manipulations[0]) && isset($manipulations['id'])) {
            $manipulations = array($manipulations);
        }

        $alreadyExistingIDs = array();

        foreach($manipulations as $modification) {
            //if($modification['file'] != 'modules/Vtiger/models/Field.php') continue;
            if(isset($alreadyExistingIDs[$modification['id']])) {
                $errors[] = 'Modification '.$modification['id'].' is existing multiple times.';
            }

            $alreadyExistingIDs[$modification['id']] = true;
            $modification['orig_search'] = $modification['search'];
            $modification['search'] = explode("[//]", $modification['search']);
            foreach($modification['search'] as $index => $value) {
                $modification['search'][$index] = trim($value);
            }

            $modification['file'] = str_replace('/', DIRECTORY_SEPARATOR, $modification['file']);

            $files[$modification['file']][] = $modification;
        }

        $filenames = array_keys($files);

        foreach($filenames as $file) {
            if(!file_exists($basePath.$file)) {
                $mandatory = false;
                foreach($files[$file] as $mod) {
                    if(empty($mod['optional'])) {
                        $mandatory = true;
                        break;
                    }
                }
                if($mandatory === true) {
                    $errors[] = $basePath . $file . ' not existing';
                }
            } elseif(!is_writeable($basePath.$file)) {
                $errors[] = $basePath.$file . ' have no write Permission for Webserver';
            } elseif(!is_writeable(dirname($basePath.$file))) {
                $errors[] = dirname($basePath.$file) . ' have no write Permission for Webserver to create backup';
            }
        }

        foreach($files as $fileName => $modification) {
            try {
                $fileCounter = $this->_applyFile($fileName, $modification, $dryRun);
                if($fileCounter > 0) {
                    $this->messages[] = 'added '.$fileCounter.' lines to '.$fileName;
                }
            } catch (\Exception $exp) {
                $errors[] = '['.$fileName.'] '.$exp->getMessage();
            }
        }

        $success = true;
        if(count($errors) > 0) {
            $success = false;
        }

        return array(
            'success' => $success,
            'errors' => $errors,
            'messages' => $this->messages,
            'hash' => $this->hash

        );
    }

    public function restorePatchFile($patchFilename, $basePath, $restoreHash, $backupFolder = false) {
        $manipulations = array();
        $this->basePath = $basePath;

        $array = XML2Array::createArray(file_get_contents($patchFilename));
        $manipulations = $array['SWPatcher']['patch'];
        if(!isset($manipulations[0]) && isset($manipulations['id'])) {
            $manipulations = array($manipulations);
        }

        if($backupFolder !== false) {
            $backupFolder = rtrim($backupFolder, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'backup_'.date('Y_m_d_H_i_s').DIRECTORY_SEPARATOR;
            mkdir($backupFolder, 0777);
        }

        foreach($manipulations as $modification) {
            $files[$modification['file']][] = $modification;
        }

        $filenames = array_keys($files);

        foreach($filenames as $file) {

            if($backupFolder !== false) {
                $backupFilename = $backupFolder . DIRECTORY_SEPARATOR . $file;
                $backupDirectory = dirname($backupFilename);
                if(!file_exists($backupDirectory)) {
                    mkdir($backupDirectory, 0777, true);
                }

                copy($basePath.'/'.$file, $backupFilename);
            }

            $this->_restoreBackup($file, $restoreHash, $backupFolder);
        }

    }
    private function _restoreBackup($filename, $hash, $corruptBackupFolder) {

        if($this->backupFolder === null) {
            $backupFilename = $filename.'.'.$this->hash.'.backup';
        } else {
            $backupFilename = $this->backupFolder . DIRECTORY_SEPARATOR . $hash. DIRECTORY_SEPARATOR . $filename;
        }

        if(!file_exists($backupFilename)) {
            echo '<p class="error">Could not restore '.$filename.'</p>';
        } else {
            copy($backupFilename, $this->basePath . DIRECTORY_SEPARATOR . $filename);

            echo '<p class="success">Successfuly Restore '.$filename.'</p>';
        }
    }

    private function _removeModification($filename, $modifications, $dryRun) {
        $changed = false;

        if(!file_exists($filename)) return;

        $content = file_get_contents($filename);
        $updateCounter = 0;


        foreach($modifications as $index => $mod) {

            if(strpos($content, '/**SWPATCHER-'.strtoupper(md5($mod['id'])).'-START') !== false) {
                $changed = true;
                $mod['modification'] = explode('\r\n', $mod['modification']);
                //$this->messages[] = '['.$filename.'] Update '.$mod['id'].'';
                $this->_ReplaceMod = $mod;
                switch (strtolower($mod['method'])) {
                    case 'insertbefore':
                    case 'insertafter':
                        $content = preg_replace('/\/\*\*SWPATCHER-'.strtoupper(md5($mod['id'])).'-START-(.*?)\*\*\\/(.*)\/\*\*SWPATCHER-'.strtoupper(md5($mod['id'])).'-FINISH\*\*\//s', '', $content);
                        break;
                    case 'replacewith':
                        $content = preg_replace('/\/\*\*SWPATCHER-'.strtoupper(md5($mod['id'])).'-START-(.*?)\*\*\\/(.*)\/\*\*SWPATCHER-'.strtoupper(md5($mod['id'])).'-FINISH\*\*\//s', $mod['search'], $content);
                        break;
                }

                $updateCounter++;
                unset($modifications[$index]);
            }
        }

        if($updateCounter > 0) {
            $this->messages[] = '['.$filename.'] Remove '.$updateCounter.' modification/s';
        }

        if($changed == true && $dryRun == false) {
            file_put_contents($filename, $content);
        }

        return $modifications;
    }

    private $_ReplaceMod = false;
    private function _clearOldModifications($filename, $modifications, $dryRun) {
        $changed = false;
        if(!file_exists($filename)) return;
        $content = file_get_contents($filename);
        $updateCounter = 0;

        foreach($modifications as $index => $mod) {

            if(strpos($content, '/**SWPATCHER-'.strtoupper(md5($mod['id'])).'-START') !== false) {
                $changed = true;
                $mod['modification'] = explode('\r\n', $mod['modification']);
                //$this->messages[] = '['.$filename.'] Update '.$mod['id'].'';
                $this->_ReplaceMod = $mod;
                $content = preg_replace_callback('/\/\*\*SWPATCHER-'.strtoupper(md5($mod['id'])).'-START-(.*?)\*\*\\/(.*)\/\*\*SWPATCHER-'.strtoupper(md5($mod['id'])).'-FINISH\*\*\//s', array($this, 'replace_old_modification'), $content);

                $updateCounter++;
                unset($modifications[$index]);
            }
        }

        if($updateCounter > 0) {
            $this->messages[] = '['.$filename.'] Update '.$updateCounter.' modification/s';
        }

        if($changed == true && $dryRun == false) {
            file_put_contents($filename, $content);
        }

        return $modifications;
    }

    private function replace_old_modification($matches) {
        $mod = $this->_ReplaceMod;

        return implode(PHP_EOL, $this->getModificationContent($mod, true));
    }

    private function backupFile($filename) {
        if(!file_exists($filename)) return;

        if($this->backupFolder === null) {
            copy($filename, $filename.'.'.$this->hash.'.backup');
        } else {
            $backupFolder = $this->backupFolder . DIRECTORY_SEPARATOR . $this->hash. DIRECTORY_SEPARATOR;

            $filename = str_replace($this->basePath, '', $filename);
            $filename = trim($filename, DIRECTORY_SEPARATOR);
            $directoryName = dirname($filename);

            if(file_exists($backupFolder .  $filename)) {
                return;
            }

            if(!file_exists($backupFolder .  $directoryName)) {
                mkdir($backupFolder .  $directoryName, 0777, true);
            }

            copy($filename, $backupFolder .  $filename);
        }
    }

    private function _applyFile($filename, $modifications, $dryRun ) {
        $fileCounter = 0;

        if($dryRun === false) {
            $this->backupFile($filename);
        }

        if($this->_OnlyRemove === true) {
            $this->_removeModification($filename, $modifications, $dryRun);
            return;
        }

        $modifications = $this->_clearOldModifications($filename, $modifications, $dryRun);

        if(count($modifications) == 0) {
            return 0;
        }
        $content = file_get_contents($filename);

        foreach($modifications as $index => $mod) {
            if(!empty($mod['duplicatecheck'])) {
                if(strpos($content, $mod['duplicatecheck']) !== false) {
                    throw new \Exception('Duplicate Warning <strong>'.$mod['id'].'</strong> (<em>'.$mod['duplicatecheck'].'</em> Please remove old modifications first.');
                }
            }

            if(!empty($mod['function'])) {
                $correctFunction = null;
            } else {
                $correctFunction = true;
            }

            $pos = 0;
            $maxNextPos = 0;
            $maxAllowedSpaces = 0;
            foreach($mod['search'] as $search) {
                if(empty($mod['function']) || $correctFunction == true) {
                    $pos = strpos($content, $search, $pos);
                } else {
                    $matches = array();
                    preg_match_all('/function[\s\n]+(\S+)[\s\n]*\(/', $content, $matches, PREG_OFFSET_CAPTURE);

                    $functionFound = false;
                    foreach($matches[1] as $index2 => $functionName) {
                        if($functionName[0] == $mod['function']) {
                            $functionFound = true;
                            $pos = strpos($content, $search, $functionName[1]);
                            if(isset($matches[1][$index2 + 1])) {
                                $maxNextPos = $matches[1][$index2 + 1][1];
                            } else {
                                $maxNextPos = 0;
                            }
                            break;
                        }
                    }
                    if($functionFound == false) {
                        throw new \Exception('Can not found function '.$mod['function']);
                    }
                    $correctFunction = true;
                }


                if($maxNextPos > 0 && $pos > $maxNextPos && empty($mod['optional'])) {
                    throw new \Exception('Can not found complete Anchor: '.$mod['id']);
                }

                $maxNextPosSpacesCheck = substr($content, $pos + strlen($search), 60);
                preg_match('/^(\s+)/',$maxNextPosSpacesCheck,$matches);
                if(!empty($matches)) {
                    $allowedSpace = strlen($matches[1]) + 6;
                } else {
                    $allowedSpace = 6;
                }
                if($allowedSpace > $maxAllowedSpaces) {
                    $maxAllowedSpaces = $allowedSpace;
                }

                $maxNextPos = $pos + strlen($search) + $allowedSpace;
                if($pos === false && empty($mod['optional'])) {
                    throw new \Exception('Can not found Anchor: '.$mod['id']);
                }

            }

            $pos2 = $pos;
            $substr_count = substr_count($content, $mod['search'][0]) - 1;
            //var_dump('$substr_count',$substr_count);
            for($i = 0; $i < $substr_count;$i++) {
                $maxNextPos = strlen($content);

                if(!empty($mod['function'])) {
                    $correctFunction = null;
                } else {
                    $correctFunction = true;
                }

                $complete = true;
                foreach($mod['search'] as $search) {
                    if(empty($mod['function']) || $correctFunction == true) {

                        $pos = strpos($content, $search, $pos);
                    } else {
                        $matches = array();
                        preg_match_all('/function[\s\n]+(\S+)[\s\n]*\(/', $content, $matches, PREG_OFFSET_CAPTURE, $pos2);

                        $functionFound = false;
                        foreach($matches[1] as $index2 => $functionName) {
                            if($functionName[0] == $mod['function']) {
                                $functionFound = true;
                                $pos = strpos($content, $search, $functionName[1]);
                                if(isset($matches[1][$index2 + 1])) {
                                    $maxNextPos = $matches[1][$index2 + 1][1];
                                } else {
                                    $maxNextPos = 1;
                                }
                                break;
                            }
                        }

                        if($functionFound == false) {
                            $complete = false;
                            $pos2 = $maxNextPos;
                            break;
                        }
                        $correctFunction = true;
                    }

                    if($pos2 === false) {
                        $complete = false;
                        $pos2 = $maxNextPos;
                        break;
                    }
                    if($pos2 !== false && $maxNextPos < $pos2) {
                        $complete = false;
                        $pos2 = $maxNextPos;
                        break;
                    }

                    $maxNextPos = $pos2 + strlen($search) + $maxAllowedSpaces;
                }

                if($complete == true) {
                    throw new \Exception('Found Anchor twice: '.$mod['id']);
                }

            }
            /*if(strpos($content, $mod['search']) === false) {
                throw new \Exception('Can not found Anchor: '.$mod['id']);
            }*/
            /*if(substr_count($content, $mod['search']) > 1) {
                throw new \Exception('Anchor found multiple times: '.$mod['id']);
            }*/

            $modifications[$index]['modification'] = explode('\r\n', $mod['modification']);
        }


        unset($content);
        $content = file($filename, FILE_IGNORE_NEW_LINES);
        $searccontent = $content;
        foreach($searccontent as $index => $line) {
            $searccontent[$index] = trim($line);
        }

        $functionFound = true;
        $searchSequences = array();
        foreach($modifications as $mod) {
            $searchSequences[$mod['id']] = '/**SWPATCHER-'.strtoupper(md5($mod['id'])).'-START-'.$this->hash.'**/';
            $functionFound = null;
            foreach($content as $ln => $line) {
                if(!empty($mod['function']) && $functionFound == null) {
                    if(strpos($line, 'function '.$mod['function']) !== false) {
                        $functionFound = true;
                    } else {
                        continue;
                    }
                }

                if($searccontent[$ln] == $mod['search'][0]) {
                    $found = true;
                    $length = 0;
                    if(count($mod['search']) > 1) {
                        foreach($mod['search'] as $length => $search) {
                            if($searccontent[$ln + $length] != $search) {
                                $found = false;
                                break;
                            }
                        }
                    }

                    if($found == true) {
//						array_unshift($mod['modification'], '');
//						array_unshift($mod['modification'], '/** Don\'t remove the Start and Finish Markup! **/');
//						array_unshift($mod['modification'], '/**SWPATCHER-'.strtoupper(md5($mod['id'])).'-START-'.$this->hash.'**/');
//						$mod['modification'][] = '';
//						$mod['modification'][] = '/**SWPATCHER-'.strtoupper(md5($mod['id'])).'-FINISH**/';

                        switch(strtolower($mod['method'])) {
                            case 'insertbefore':
                                $insertPosition = $ln;
                                break;
                            case 'insertafter':
                                $insertPosition = $ln + $length + 1;
                                break;
                            case 'replacewith':
                                $insertPosition = $ln;

                                for($i = 0; $i < count($mod['search']); $i++) {
                                    unset($content[$insertPosition + $i]);
                                    unset($searccontent[$insertPosition + $i]);
                                }

                                break;
                        }

                        $content = $this->insertIntoArray($content, $insertPosition, $this->getModificationContent($mod, true));
                        $searccontent = $this->insertIntoArray($searccontent, $insertPosition, $this->getModificationContent($mod, true));

                        $fileCounter += count($mod['modification']);
                        break;
                    }
                }
            }
        }

        $fileContent = implode(PHP_EOL, $content);

        foreach($searchSequences as $id => $string) {
            if(strpos($fileContent, $string) === false) {
                throw new \Exception('Could not found <strong>'.$id.'</strong> replacement after all manipulations are done');
            }
        }

        if($dryRun === false) {
            file_put_contents($filename, $fileContent);
        }

        return $fileCounter;
    }

    // Copyright: Joraid
    // http://stackoverflow.com/questions/3353745/how-to-insert-element-into-array-to-specific-position
    // modifications by Stefan Warnat
    private  function insertIntoArray($array, $index, $val)
    {
        $size = count($array);

        if (!is_int($index) || $index < 0 || $index > $size)
        {
            return -1;
        }
        else
        {
            $temp = array_slice($array, 0, $index);

            if(!is_array($val)) {
                $temp[] = $val;
            } else {
                foreach($val as $line) {
                    $temp[] = $line;
                }
            }

            return array_merge($temp, array_slice($array, $index, $size));
        }
    }
}

class XML2Array {

    private static $xml = null;
    private static $encoding = 'UTF-8';

    /**
     * Initialize the root XML node [optional]
     * @param $version
     * @param $encoding
     * @param $format_output
     */
    public static function init($version = '1.0', $encoding = 'UTF-8', $format_output = true) {
        self::$xml = new \DOMDocument($version, $encoding);
        self::$xml->formatOutput = $format_output;
        self::$encoding = $encoding;
    }

    /**
     * Convert an XML to Array
     * @param string $node_name - name of the root node to be converted
     * @param array $arr - aray to be converterd
     * @return \DOMDocument
     */
    public static function &createArray($input_xml) {
        libxml_use_internal_errors(true);
        $xml = self::getXMLRoot();
        if(is_string($input_xml)) {

            $parsed = $xml->loadXML($input_xml);
            foreach(libxml_get_errors() as $e){
                if(isset($e->message)) {
                    $error = $e->message.' Line '.$e->line.' Col '.$e->column;
                }
            }

            //var_dump(libxml_get_errors());
            if(!$parsed) {
                throw new \Exception('[XML2Array] Error parsing the XML string:<br/>' . $error);
            }
        } else {
            if(get_class($input_xml) != 'DOMDocument') {
                throw new \Exception('[XML2Array] The input XML object should be of type: DOMDocument.');
            }
            $xml = self::$xml = $input_xml;
        }
        $array[$xml->documentElement->tagName] = self::convert($xml->documentElement);
        self::$xml = null;    // clear the xml node in the class for 2nd time use.
        return $array;
    }

    /**
     * Convert an Array to XML
     * @param mixed $node - XML as a string or as an object of DOMDocument
     * @return mixed
     */
    private static function &convert($node) {
        $output = array();

        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
                $output['@cdata'] = trim($node->textContent);
                break;

            case XML_TEXT_NODE:
                $output = trim($node->textContent);
                break;

            case XML_ELEMENT_NODE:

                // for each child node, call the covert function recursively
                for ($i=0, $m=$node->childNodes->length; $i<$m; $i++) {
                    $child = $node->childNodes->item($i);
                    $v = self::convert($child);
                    if(isset($child->tagName)) {
                        $t = $child->tagName;

                        // assume more nodes of same kind are coming
                        if(!isset($output[$t])) {
                            $output[$t] = array();
                        }
                        $output[$t][] = $v;
                    } else {
                        //check if it is not an empty text node
                        if($v !== '') {
                            $output = $v;
                        }
                    }
                }

                if(is_array($output)) {
                    // if only one node of its kind, assign it directly instead if array($value);
                    foreach ($output as $t => $v) {
                        if(is_array($v) && count($v)==1) {
                            $output[$t] = $v[0];
                        }
                    }
                    if(empty($output)) {
                        //for empty nodes
                        $output = '';
                    }
                }

                // loop through the attributes and collect them
                if($node->attributes->length) {
                    $a = array();
                    foreach($node->attributes as $attrName => $attrNode) {
                        $a[$attrName] = (string) $attrNode->value;
                    }
                    // if its an leaf node, store the value in @value instead of directly storing it.
                    if(!is_array($output)) {
                        $output = array('@value' => $output);
                    }
                    $output['@attributes'] = $a;
                }
                break;
        }
        return $output;
    }

    /*
     * Get the root XML node, if there isn't one, create it.
     */
    private static function getXMLRoot(){
        if(empty(self::$xml)) {
            self::init();
        }
        return self::$xml;
    }
}
