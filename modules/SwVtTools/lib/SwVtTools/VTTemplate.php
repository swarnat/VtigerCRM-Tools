<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Stefan Warnat <support@stefanwarnat.de>
 * Date: 12.02.14 23:36
 * You must not use this file without permission.
 */
namespace SwVtTools;

class VTTemplate {
    /**
     * @var VTEntity
     */
    protected $_context = "";

    /**
     * @param $context VTEntity
     */
    public function __construct($context) {
        $this->_context = $context;
    }

    public static function parse($template, $context) {
        if(strpos($template, '$') !== false || strpos($template, '?') !== false) {
            $objTemplate = new VTTemplate($context);
            $template = $objTemplate->render($template);
        }

        return $template;
    }
    public function render($template) {

        $template = html_entity_decode($template);

        // VTexpressions NEW
        $return = preg_replace_callback('/\\${(.*?)}}>/s', array($this,"functionHandler"), $template);

        // VTexpressions
        $return = preg_replace_callback('/\<\?p?h?p?(.*?)\?\>/s', array($this,"functionHandler"), $return);

        // Variable in Brackets
        $return = preg_replace_callback('/{\\$([a-zA-Z0-9_]*?)}/s', array($this,"matchHandler"), $return);

        // $asdf or $(assigned_user_id : (Users) signature)
        $return = preg_replace_callback('/\\$(\w+|(\[([a-zA-Z0-9]*)((,(.*))?)\])|({(.*?)}}>)|\((\w+) ?: \(([_\w]+)\) (\w+)\))/', array($this,"matchHandler"), $return);

        return $return;
    }

    protected function functionHandler($match) {

        $parser = new ExpressionParser($match[1], $this->_context, false); # Last Parameter = DEBUG

        $parser->run();

        return $newValue = $parser->getReturn();
    }

    // $((account_id: (Accounts)) cf_661)
    protected function matchHandler($match){

        // Wenn count($match) == 2, dann nur $email und keine referenzierten Felder
        if(count($match) == 2) {

            // Special Variables
            if($match[0] == '$current_user_id') {
                global $current_user, $adb ;
                $sql = "SELECT id FROM vtiger_ws_entity WHERE name = 'Users'";
                $result = $adb->query($sql);
                $wsTabId = $adb->query_result($result, 0, "id");

                return $wsTabId."x".$current_user->id;
            }

            $fieldname = $match[1];
            $fieldvalue = $this->_context->get($fieldname);

            if($fieldvalue === false) {
                return '$'.$fieldname;
            }

            if(!empty($fieldvalue)) {
                return $fieldvalue;
            }

        // it is a global function
        } elseif(substr($match[0], 0, 2) == "$[") {
            $function = strtolower($match[3]);

            if(count($match) > 4 && $match[4] != "") {
                $parameter = explode(",", $match[6]);
                for($i = 0; $i < count($parameter); $i++) {
                    $parameter[$i] = trim($parameter[$i], "'\" ");
                }
            } else {
                $parameter = false;
            }

            switch($function) {
                case "url":
                    if($parameter != false && count($parameter) > 0) {
                        $parameter[0] = intval($parameter[0]);
                        $objTMP = VTEntity::getForId($parameter[0]);
                        global $site_URL;return $site_URL.'/index.php?action=DetailView&module='.$objTMP->getModuleName().'&record='.$parameter[0];
                    }
                    break;
                case "now":
                    $format = "Y-m-d";
                    $time = time();
                    if($parameter != false) {
                        if(!empty($parameter[0])) {
                            $time += (intval($parameter[0]) * 86400);
                        }

                        if(!empty($parameter[1])) {
                            $format = $parameter[1];
                        }
                    }
                    return date($format, $time);
                    break;
                case "entityname":
                    if($parameter != false) {
                        $parameter[0] = VTTemplate::parse($parameter[0], $this->_context);

                        if(strpos($parameter[0], "x") !== false) {
                            $crmid = explode("x", $parameter[0]);
                            $crmid = intval($crmid[1]);
                        } else {
                            $crmid = intval($parameter[0]);
                        }
                        global $adb;

                        $sql = "SELECT setype FROM vtiger_crmentity WHERE crmid=?";
                        $result = $adb->pquery($sql, array($crmid));
                        $data = $adb->fetchByAssoc($result);
                        $return = getEntityName($data['setype'], array($crmid));
                        return $return[$crmid];
                    } else {
                        return "";
                    }
                    break;
            }

        } else {
            preg_match('/\((\w+) ?: \(([_\w]+)\) (\w+)\)/', $match[1], $matches);

            list($full, $referenceField, $referenceModule, $fieldname) = $matches;
            if($referenceField == "smownerid") {
                $referenceField = "assigned_user_id";
            }

            if($referenceModule === '__VtigerMeta__'){
                return $this->_getMetaValue($fieldname);
            } else {
                if($referenceField != "current_user") {

                    $referenceId = $this->_context->get($referenceField);
                    if($referenceId==null)
                        return "";

                } else {
                    global $current_user;
                    $referenceId = $current_user->id;
                }

                $entity = VTEntity::getForId($referenceId, $referenceModule=="Users"?"Users":false);
                return $entity->get($fieldname);
            }
        }

    }

    protected function _getMetaValue($fieldname){
   		global $site_URL, $PORTAL_URL, $current_user;

   		switch($fieldname) {
   			case 'date'					:	return getNewDisplayDate();
   			case 'time'					:	return date('h:i:s');
   			case 'dbtimezone'			:	return DateTimeField::getDBTimeZone();
   			case 'crmdetailviewurl'		:	$recordId = $this->_context->getId();
   											$moduleName = $this->_context->getModuleName();
   											return $site_URL.'/index.php?action=DetailView&module='.$moduleName.'&record='.$recordId;

   			case 'portaldetailviewurl'	: 	$recordId = $this->_context->getId();
   											$moduleName = $this->_context->getModuleName();
   											$recorIdName='id';
   											if($moduleName == 'HelpDesk') $recorIdName = 'ticketid';
   											if($moduleName == 'Faq') $recorIdName = 'faqid';
   											if($moduleName == 'Products') $recorIdName = 'productid';
   											return $PORTAL_URL.'/index.php?module='.$moduleName.'&action=index&'.$recorIdName.'='.$recordId.'&fun=detail';
   			case 'siteurl'				: return $site_URL;
   			case 'portalurl'			: return $PORTAL_URL;
   			default: '';
   		}
   	}
}