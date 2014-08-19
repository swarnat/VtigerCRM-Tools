<?php
/**
 This File was developed by Stefan Warnat <vtiger@stefanwarnat.de>

 It belongs to the Workflow Designer and must not be distributed without complete extension
**/
namespace SwVtTools;

use VTInventoryEntity;
use \CRMEntity;

/**
 * VTEntity
 * @version 1.0
 *
 * 1.0 add Dummy Functions to allow "global" not bind Workflows
 */
require_once('include/utils/utils.php');

class VTEntity
{
    /*
        Object Member
    */
    protected $_id = 0;
    protected $_wsid = 0;

    protected $_data = false;
    protected $_moduleName = "";

    protected $_changed = false;

    protected $_references = array();

    protected $_environment = array();

    protected $_deleted = false;

    protected $_isNew = false;

    protected $_isDummy = false;

    /*
        Static Members
    */
    protected static $_cache = array();
    protected static $_user = false;

    protected static $_oldRequest = false;

    protected $_saveRequest = array();

    protected $_internalObj = false;

    protected $_isInventory = false;
    /**
     * @static
     * @param int $id - CRMId des Records
     * @param string $module_name - ModuleName des records
     * @param Users $user - [Optional] Used for Object Access
     * @return VTEntity
     */
    public static function getForId($id, $module_name = false, $user = false) {
        if(empty($id)) {
            return self::getDummy();
        }

        if(strpos($id, "x") !== false) {
            $idParts = explode("x", $id);
            $id = $idParts[1];

            if(empty($module_name)) {
                global $adb;
                $sql = "SELECT name FROM vtiger_ws_entity WHERE id = ".intval($idParts[0]);
                $result = $adb->query($sql);
                $module_name = $adb->query_result($result, 0, "name");
            }
        }
        if(strpos($id, "@") !== false) {
            $id = explode("@", $id);
            $id = $id[0];
        }

        if($module_name == false) {
            global $adb;
            $sql = "SELECT setype FROM vtiger_crmentity WHERE crmid = ".intval($id);
            $result = $adb->query($sql);
            $module_name = $adb->query_result($result, 0, "setype");
        }
        if(empty($module_name)) {
            return false;
        }
        if($module_name == "Calendar" || $module_name == "Events") { $module_name = vtws_getCalendarEntityType($id); }

        if($user === false) {
            $userID = VTEntity::$_user->id;
        } else {
            $userID = $user->id;
        }

        if(isset(VTEntity::$_cache[$userID][$id])) {
            return VTEntity::$_cache[$userID][$id];
        }
        global $current_user;

        if($module_name == "Invoice" || $module_name == "Quotes" || $module_name == "SalesOrder" || $module_name == "PurchaseOrder") {
            VTEntity::$_cache[$userID][$id] = new VTInventoryEntity($module_name, $id);
        } else {
            VTEntity::$_cache[$userID][$id] = new VTEntity($module_name, $id);
        }

        return VTEntity::$_cache[$userID][$id];
    }

    public static function setUser($user) {
        // Only change if realy change take place
        if(!empty(VTEntity::$_user) && !empty($user) && $user->id == VTEntity::$_user->id)
            return;

        VTEntity::$_user = $user;

        // New User means, new Permissions -> Clear cache
        #VTEntity::$_cache = array();
    }

    /**
     * Gibt den aktuell benutzten User zurück
     * Wenn gesetzt, dann wird dieser user gesetzt, wenn keienr gesetzt ist
     * @static
     * @param bool $defaultUser
     * @return bool
     */
    public static function getUser($defaultUser = false) {
        if(self::$_user === false) {
            if($defaultUser === false) {
                VTEntity::$_user = Users::getActiveAdminUser();
            } else {
                VTEntity::$_user = $defaultUser;
            }
        }

        return VTEntity::$_user;
    }

    protected function __construct($module_name, $id) {
        if($module_name == "Activity") {
            $module_name = "Calendar";
        }

        if($module_name == "dummy") {
            $this->_isDummy = true;

            $this->_id = $id;
            $this->_moduleName = $module_name;
            $this->_data = array();
        } else {
            $this->_id = $id;

            if(!empty($id)) {
                $wsid = vtws_getWebserviceEntityId($module_name, $id);
                $this->_wsid = $wsid;
            } else {
                $this->_data = array("assigned_user_id" => self::$_user->id);
            }

            $this->_moduleName = $module_name;
        }
    }
    public function isInventory() {
        return $this->_isInventory;
    }
    public function clearEnvironment() {
        $this->_environment = array();
    }

    public function getEnvironment($key = false) {
        if($key === false) {
            return $this->_environment;
        } else {
            return isset($this->_environment[$key])?$this->_environment[$key]:false;
        }
    }

    public function loadEnvironment($env) {
        if(is_array($env)) {
            $this->_environment = $env;
        }
    }

    public function setEnvironment($key, $value, $task = false) {
        if($task !== false) {
            $env = $task->get("env");

            if($env !== -1 && !empty($env[$key])) {
                $this->_environment[$env[$key]] = $value;
            }
        } else {
            $this->_environment[$key] = $value;
        }
    }

    public function isDummy() {
        return $this->_isDummy;
    }
    public function setIsNew($value) {
        $this->_isNew = $value;
    }
    public function isNew() {
        return $this->_isNew;
    }
    public function delete() {
        if($this->_isDummy) {
            return;
        }

        $this->_deleted = true;

        $this->prepareTransfer();

        try {

            $recordModel = \Vtiger_Record_Model::getInstanceById($this->_id, $this->getModuleName());
            $recordModel->delete();

		} catch(Exception $exp) {
			if($exp->getCode() == "DATABASE_QUERY_ERROR") {
				global $adb;
				$handleResult = $this->_handleDatabaseError($adb->database->_errorMsg);
				$this->_data = array();
			} else {
			
				#error_log("ERROR RETRIEVE ".$this->getWsId()." ".$exp->getMessage());
				throw new $exp;
			}		
		}

        $this->_data = array();

        $this->afterTransfer();
    }
    public function isAvailable() {
        return !$this->_deleted;
    }

    public function getDetailUrl() {
        return "index.php?module=".$this->getModuleName()."&view=Detail&record=".$this->getId();
    }

    public function getData() {
        global $current_user, $adb;

        if($this->_isDummy) {
            return array();
        }

        require_once 'include/Webservices/Retrieve.php';

        if($this->_data === false) {
            #$crmObj = CRMEntity::getInstance($this->_moduleName);
            #$this->_data = $crmObj;

            #$this->_data->id = $this->_id;
            #$this->_data->mode = "edit";
            #$data = $this->_data;

            #$this->_data->retrieve_entity_info($this->_id, $this->_moduleName);

            $this->prepareTransfer();

            try {
                global $current_user;
                $oldCurrentUser = $current_user;
                $current_user = $useUser;

                $focus = CRMEntity::getInstance($this->getModuleName());
                $focus->id = $this->_id;
                $focus->mode = 'edit';
                $focus->retrieve_entity_info($this->_id, $this->getModuleName());
                $this->_data = $focus->column_fields;

                /* both values are irrelevant use ->id and ->getModuleName() */
                unset($this->_data['record_id']);
                unset($this->_data['record_module']);

                $current_user = $oldCurrentUser;
            } catch(Exception $exp) {
                $current_user = $oldCurrentUser;
				if($exp->getCode() == "DATABASE_QUERY_ERROR") {
					global $adb;
					$handleResult = $this->_handleDatabaseError($adb->database->_errorMsg);
					$this->_data = array();
				} elseif($exp->getCode() == "ACCESS_DENIED" && $exp->getMessage() == "Permission to perform the operation is denied") {
                    $sql = "SELECT setype FROM vtiger_crmentity WHERE crmid = ".$this->_id;
                    $checkTMP = $adb->query($sql);
                    if($adb->num_rows($checkTMP) == 0) {
                        #Workflow2::error_handler(E_NONBREAK_ERROR, "Record ".$this->_id." don't exist in the database. Maybe you try to load data from a group?", $exp->getFile(), $exp->getLine());
                        $this->_data = array();
                        return array();
                    }
                    if($adb->query_result($checkTMP, 0, "setype") != $this->getModuleName()) {
                        
						throw new \Exception("You want to get a field from ".$this->getModuleName()." Module, but the ID is from module ".$adb->query_result($checkTMP, 0, "setype").".");
                        $this->_data = array();
                        return array();
                    }
                    $entity = VTEntity::getForId($this->_id);
                    #if(empty($entit))
                    #var_dump($this->getModuleName());
                    #var_dump($this->_id);
				} else {

					#error_log("ERROR RETRIEVE ".$this->getWsId()." ".$exp->getMessage());
					throw new $exp;
				}
            }

            $this->afterTransfer();

            if($this->_moduleName == "Emails") {
                require_once("include/Zend/Json.php");
                \Zend_Json::$useBuiltinEncoderDecoder = false;
                $sql = "SELECT * FROM vtiger_emaildetails WHERE emailid = ".$this->getId();
                $result = $adb->query($sql);

                $to_email = $adb->query_result($result, 0, "to_email");

                #if(VtUtils::is_utf8($to_email)) $to_email = utf8_encode($to_email);
                $this->_data["saved_to"] = implode(",", \Zend_Json::decode(html_entity_decode($to_email)));
                $this->_data["from_email"] = $adb->query_result($result, 0, "from_email");
            }

            // Fix for vtiger bug
            if($this->_moduleName == "SalesOrder" && $this->_data["enable_recurring"] == "0") {
               $this->_data["invoicestatus"] = "AutoCreated";
            }

        }

        #return $this->_data->column_fields;
        return $this->_data;
    }
    public function getModuleName() {
        return $this->_moduleName;
    }

    public function getWsId() {
        if($this->_isDummy) {
            return "0x0";
        }

        $parts = explode("x", $this->_wsid);
        if($parts[0] == "9") {
            global $adb;
            $sql = "SELECT activitytype FROM vtiger_activity WHERE activityid = ".intval($parts[1]);
            $result = $adb->query($sql);
            if($adb->num_rows($result) > 0) {
                $type = $adb->query_result($result, 0, "activitytype");
                if($type != "Task") {
                    $parts[0] = 18;
                    $this->_wsid = implode("x", $parts);
                }
            }
        }
        return $this->_wsid;
    }
    public function getId() {
        return $this->_id;
    }

    public function get($key) {
        if($key == "crmid")
            return $this->_id;
        if($key == "id")
            return $this->_id;

        $data = $this->getData();

        if(!isset($data[$key]))
            return false;

        return $data[$key];
    }
    public function clearData() {
        $this->_data = false;
    }

    public function getReference($module, $field) {
        if($field == "smownerid") {
            $field = "assigned_user_id";
        }

        if(!empty($this->_references[$field]))
            return $this->_references[$field];

        $id = $this->get($field);

        if(empty($id)) {
            return false;
        }

        if($module === false) {
            $module = $this->getModuleName();
        }

        $this->_references[$field] = self::getForId($id, $module);

        return $this->_references[$field];
    }

    public function set($key, $value) {
        $data = $this->getData();

        if($this->_data[$key] != $value) {
            $this->_data[$key] = $value;
            $this->_changed = true;
        }
    }

    protected function prepareTransfer() {
        global $current_user, $oldCurrentUser;

        $this->_oldRequest = $_REQUEST;
        unset($_REQUEST);
        $_REQUEST = $this->_saveRequest;

        $useUser = \Users::getActiveAdminUser();
        $oldCurrentUser = $current_user;
        $current_user = $useUser;

        # Quotes absichern
        $_REQUEST['ajxaction'] = 'DETAILVIEW';
        #$_REQUEST['action'] = 'MassEditSave';
        $_REQUEST['search'] = true;
        $_REQUEST['submode'] = true;

        VTEntity::$_user->currency_decimal_separator = ".";
        VTEntity::$_user->currency_grouping_separator = "";
        VTEntity::$_user->column_fields["currency_decimal_separator"] = ".";
        VTEntity::$_user->column_fields["currency_grouping_separator"] = "";

        $current_user->currency_decimal_separator = ".";
        $current_user->currency_grouping_separator = "";

        $current_user->column_fields["currency_decimal_separator"] = ".";
        $current_user->column_fields["currency_grouping_separator"] = "";

        if($this->getModuleName() == "Contacts") {
            $_FILES = array("index" => array("name" => "", "size" => 0));
        }
    }

    protected function afterTransfer() {
        global $current_user, $oldCurrentUser;

        $_REQUEST = $this->_oldRequest;
        $current_user = $oldCurrentUser;
    }

    public function createRecord() {
        global $current_user;

        if($this->_isDummy) {
            return;
        }

        require_once("include/Webservices/Create.php");
        $this->prepareTransfer();

        // WICHTIG!
        if($this->_moduleName == "Events") {
            $_REQUEST["set_reminder"] = "No";
        } else {
            $_REQUEST["set_reminder"] = "Yes";
        }

        try {
            $newEntity = CRMEntity::getInstance($this->getModuleName());
            $newEntity->mode = '';
            $metaHandler = self::getMetaHandler($this->getModuleName());

//            $ownerFields = $metaHandler->getOwnerFields();
            $moduleFields = $metaHandler->getModuleFields();

            foreach($this->_data as $key => $newValue) {
                $fieldInstance = $moduleFields[$key];
                if(empty($fieldInstance)) {
                    throw new \Exception('Field '.$key.' not found in module '.$this->getModuleName().'.');
                }
                $fieldDataType = $fieldInstance->getFieldDataType();

                if('reference' == $fieldDataType || 'owner' == $fieldDataType) {
                    $newValue = $this->getCrmId($newValue);
                }

                $newEntity->column_fields[$key] = $newValue;
            }

            $newEntity->save($this->getModuleName());

        } catch(Exception $exp) {
            if($exp->getCode() == "DATABASE_QUERY_ERROR") {
                global $adb;
                $handleResult = $this->_handleDatabaseError($adb->database->_errorMsg);
				return;
            }

            if($exp->getCode() == "MANDATORY_FIELDS_MISSING") {
                $handleResult = $this->_handleMandatoryError($exp);
                if($handleResult !== false) {
                    return;
                }
            }

            throw new $exp;
        }

        $this->afterTransfer();

        $this->_id = $this->getCrmId($newEntity->id);

        $wsid = vtws_getWebserviceEntityId($this->getModuleName(), $this->_id);
        $this->_wsid = $wsid;

        return $result;
    }

    public function getCrmId($idString) {
        if(strpos($idString, "x") !== false) {
            $idParts = explode("x", $idString);
            return $idParts[1];
        }

        if(strpos($idString, "@") !== false) {
            $id = explode("@", $idString);
            return $id[0];
        }

        if(is_numeric($idString)) {
            return $idString;
        }

        return false;
    }

    /** EntityData Functions BEGIN */

    /**
     * @param string $key
     * @param mixed $value
     * @param string $mode Simple|Multi
     *
     * Function set the EntityData into the Record and replace if $mode = simple and key already exist
     */
    public function addEntityData($key, $value, $assigned_to = false, $mode = "simple") {
        global $adb;

        if($mode == "simple") {
            $this->removeEntityData($key);

            $sql = "INSERT INTO vtiger_wf_entityddata SET crmid = ?, `key` = ?, `value` = ?, assigned_to = ?, `mode` = ?";
            $adb->pquery($sql, array($this->_id, $key, @serialize($value), $assigned_to, $mode));
        } else {
            $sql = "INSERT INTO vtiger_wf_entityddata SET crmid = ?, `key` = ?, `value` = ?, assigned_to = ?, `mode` = ?";
            $adb->pquery($sql, array($this->_id, $key, @serialize($value), $assigned_to, $mode));
        }
    }

    public function getEntityData($key) {
        global $adb;

        $sql = "SELECT * FROM vtiger_wf_entityddata WHERE crmid = ? AND `key` = ?";
        $result = $adb->pquery($sql, array($this->_id, $key));

        if($adb->num_rows($result) == 0) {
            return -1;
        }

        $return = array();
        while($row = $adb->fetchByAssoc($result)) {
            if($row["mode"] == "simple") {
                $return = @unserialize(html_entity_decode($row["value"]));
                break;
            } else {
                $return[] = @unserialize(html_entity_decode($row["value"]));
            }
        }

        return $return;
    }

    public function removeEntityData($key, $dataID = false) {
        global $adb;

        $sql = "DELETE FROM vtiger_wf_entityddata WHERE `crmid` = ? AND `key` = ?".($dataID!==false?" AND dataid = ?":"");
        $values = array($this->_id, $key);
        if($dataID !== false) {
            $values[] = $dataID;
        }

        $adb->pquery($sql, $values);
    }
    public function existEntityData($key) {
        global $adb;

        $sql = "SELECT crmid FROM vtiger_wf_entityddata WHERE crmid = ? AND `key` = ?";
        $result = $adb->pquery($sql, array($this->_id, $key));

        if($adb->num_rows($result) > 0) {
            return true;
        } else {
            return false;
        }
    }
    /** EntityData Functions END */

    protected function _handleMandatoryError($exp) {
        $fieldname = trim(substr($exp->getMessage(), 0, strpos($exp->getMessage(), " ")));
        $sql = "SELECT defaultvalue FROM vtiger_field WHERE tabid = '".getTabid($this->getModuleName())."' AND columnname = ?";
        global $adb;
        $defaultRST = $adb->pquery($sql, array($fieldname));
        $defaultValue = $adb->query_result($defaultRST, 0, "defaultvalue");
        if(!empty($defaultValue)) {
            $this->set($fieldname, $defaultValue);
            $this->save();
            return true;
        }
        return false;
    }
    public function _handleDatabaseError($errorMsg) {
		throw new \Exception($errorMsg);
    }
    public function save() {
        if($this->_deleted == true) {
            return;
        }
        if($this->_data == false) {
            return;
        }
        if($this->_isDummy) {
            return;
        }
        if(empty($this->_id)) {
            $result = $this->createRecord();
            return $result;
        }

        if(VTEntity::$_user === false || VTEntity::$_user->is_admin != "on") {
            $useUser = Users::getActiveAdminUser();
        } else {
            $useUser = VTEntity::$_user;
        }

        $oldFiles = $_FILES;
        require_once("include/Webservices/Update.php");

        if($this->_changed == false)
            return;

        // I must prevent $ajaxSave to come true in vtws_update. This will remove all date fields !

        unset($_FILES);
        #$oldA = $_REQUEST['file'];
        #$oldB = $_REQUEST['action'];
        #$_REQUEST['file'] = "VTEntitiy";
        #$_REQUEST['action'] = "WebServiceSave";

        global $current_user, $default_charset;
        $oldCurrentUser = $current_user;
        $current_user = $useUser;

        $this->prepareTransfer();
        try {
            require_once('data/CRMEntity.php');
            $metaHandler = self::getMetaHandler($this->getModuleName());

            $focus = CRMEntity::getInstance($this->getModuleName());
            $focus->id = $this->_id;
            $focus->mode = 'edit';
            $focus->retrieve_entity_info($this->_id, $this->getModuleName());
            $focus->clearSingletonSaveFields();
            $focus->column_fields = \DataTransform::sanitizeDateFieldsForInsert(     $focus->column_fields,   $metaHandler);
            $focus->column_fields = \DataTransform::sanitizeCurrencyFieldsForInsert( $focus->column_fields,   $metaHandler);
            $moduleFields = $metaHandler->getModuleFields();

            foreach($focus->column_fields as $key => $value) {
                if($this->_data[$key] != $value && !in_array($key, array('record_id', 'record_module'))) {
                    //var_dump($key, $this->_data[$key], $value);
                    $newValue = $this->_data[$key];
                    $fieldInstance = $moduleFields[$key];
                    if(empty($fieldInstance)) {
                        throw new \Exception('Field '.$key.' not found in module '.$this->getModuleName().'.');
                    }
                    $fieldDataType = $fieldInstance->getFieldDataType();

                    if('reference' == $fieldDataType || 'owner' == $fieldDataType) {
                        $newValue = $this->getCrmId($newValue);
                        if($focus->column_fields[$key] == $newValue) {
                            continue;
                        }
                    }
                    //var_dump('set');
                    $focus->column_fields[$key] = $newValue;
                }
            }

            foreach ($focus->column_fields as $fieldName => $fieldValue) {
                $focus->column_fields[$fieldName] = html_entity_decode($fieldValue, ENT_QUOTES, $default_charset);
            }

            $_REQUEST['file'] = '';
            $_REQUEST['ajxaction'] = '';

            // Added as Mass Edit triggers workflow and date and currency fields are set to user format
            // When saving the information in database saveentity API should convert to database format
            // and save it. But it converts in database format only if that date & currency fields are
            // changed(massedit) other wise they wont be converted thereby changing the values in user
            // format, CRMEntity.php line 474 has the login to check wheather to convert to database format
            $actionName = $_REQUEST['action'];
            $_REQUEST['action'] = '';

            // For workflows update field tasks is deleted all the lineitems.
            $focus->isLineItemUpdate = false;

            $focus->save($this->getModuleName());

            //// Reverting back the action name as there can be some dependencies on this.
            //$_REQUEST['action'] = $actionName;

            //$result = vtws_update($this->_data, $useUser);
            $current_user = $oldCurrentUser;
        } catch(Exception $exp) {
            $current_user = $oldCurrentUser;
            if($exp->getCode() == "DATABASE_QUERY_ERROR") {
                global $adb;
                $handleResult = $this->_handleDatabaseError($adb->database->_errorMsg);
                return;
            }
            if($exp->getCode() == "MANDATORY_FIELDS_MISSING") {
                $handleResult = $this->_handleMandatoryError($exp);
                if($handleResult !== false) {
                    return;
                }
            }

			throw $exp;
        }
        $this->afterTransfer();

        $this->_changed = false;
        $_FILES = $oldFiles;
    }

    /**
     * @param $module
     * @return mixed
     */
    public static function GetMetaHandler($module) {
        if(isset(self::$_cache['metaHandler_'.$module])) {
            return self::$_cache['metaHandler_'.$module];
        }

        global $current_user;

        $moduleHandler = vtws_getModuleHandlerFromName($module, $current_user);
        self::$_cache['metaHandler_'.$module] = $moduleHandler->getMeta();

        return self::$_cache['metaHandler_'.$module];
    }

    /**
     * @return \Vtiger_Record_Model
     */
    public function getModel() {
        if($this->_isDummy) {
            return false;
        }

        $this->save();

        return \Vtiger_Record_Model::getInstanceById($this->_id, $this->_moduleName);
    }

    /**
     * @return CRMEntity
     */
    public function getInternalObject() {
        if($this->_isDummy) {
            return false;
        }

        if($this->_internalObj !== false) {
            return $this->_internalObj;
        } else {
            $obj = CRMEntity::getInstance($this->_moduleName);
            $obj->id = $this->_id;
            $obj->retrieve_entity_info($this->_id,$this->_moduleName);
            $this->_internalObj = $obj;
        }

        return $this->_internalObj;
    }

    public static function create($module) {
        if($module == "Invoice" || $module == "Quotes" || $module == "SalesOrder") {
            return new VTInventoryEntity($module, 0);
        } else {
            return new VTEntity($module, 0);
        }

    }

    /**
     * Function get a dummy Entity, which don't represent a Record
     * @return VTEntity
     */
    public static function getDummy() {
        return new VTEntity("dummy", 0);
    }

}
