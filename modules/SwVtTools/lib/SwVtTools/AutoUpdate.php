<?php
namespace SwVtTools;

/**
 * Created by JetBrains PhpStorm.
 * User: Stefan Warnat <support@stefanwarnat.de>
 * Date: 10.01.14 16:15
 * You must not use this file without permission.
 */
    class AutoUpdate {
        const serverHTTP = "aHR0cDovL3NlcmlhbC5zaG9wLnN0ZWZhbndhcm5hdC5kZS9zZXJpYWwv";
        const serverHTTPS = "aHR0cHM6Ly9zaG9wLnN0ZWZhbndhcm5hdC5kZS9zZXJpYWwv";

        private $_extension = false;
        private $_channel = "stable";
        private $_licenseKey = false;
        private $licenseDir = "";
        private $_latestExtensionData = false;
        private $_client = false;

        public function __construct($extension, $channel = "stable", $licenseDirectory = false) {
            $this->_extension = $extension;
            $this->_channel = $channel;

            global $root_directory;
            $this->licenseDir = $licenseDirectory!==false?$licenseDirectory:$root_directory."/modules/".$this->_extension."/";

            global $site_URL;
            if(file_exists($this->licenseDir.sha1($site_URL).".dat")) {
                $content = gzfile($this->licenseDir.sha1($site_URL).".dat");
                $lastHash = trim($content[0]);
                $this->_licenseKey = trim($content[1]);
            } else {
                $this->_licenseKey = md5("free");
            }

        }

        public function getUrl() {
            if(extension_loaded("curl")) {
                $url = self::serverHTTPS;
            } else {
                $url = self::serverHTTP;
            }

            return base64_decode($url);
        }
        public function getCurrentInstalledVersion() {
            $db = \PearDatabase::getInstance();

            $sql = "SELECT version FROM vtiger_tab WHERE name = ?";
            $result = $db->pquery($sql, array($this->_extension));
            if($db->num_rows($result) == 0) {
                return false;
            }

            $version = $db->query_result($result, 0, "version");
            return floatval($version);
        }

        public function getUpdateUrl() {
            if($this->_latestExtensionData === false) {
                $this->getLatestVersion(false);
            }
            return $this->_latestExtensionData["url"];
        }

        public function getChangelog() {
            if($this->_latestExtensionData === false) {
                $this->getLatestVersion();
            }

            return $this->_latestExtensionData["changelog"];
        }
        public function connect() {
            if($this->_client !== false) {
                return;
            }
            $url = $this->getUrl();

            require_once(dirname(__FILE__)."/nusoap/nusoap.php");

            $this->_client = new \wf_nusoap_client($url, false);
            $err = $this->_client->getError();
            if(!empty($_GET["stefanDebug"])) {
                /* ONLY DEBUG*/ echo "<pre>";var_dump($this->_client->debug_str);
            }
        }

        public function getLatestVersion($output = true) {
            $this->connect();

            if($this->_latestExtensionData === false) {
                global $vtiger_current_version;

                $data=$this->_client->call("checkVersion2",array($vtiger_current_version, $this->_extension, $this->_channel, $this->_licenseKey));
                if(!empty($_GET["stefanDebug"])) {
                    /* ONLY DEBUG*/ echo "<pre>";var_dump($this->_client->debug_str);
                }

                $result = @unserialize($data);
                $this->_latestExtensionData = $result;
            }

            if($output && isset($this->_latestExtensionData["license_expired"]) && $this->_latestExtensionData["license_expired"] == true) {
                echo "<span style='color:red;font-weight:bold;'>WARN:</span> Your license expired on ".\DateTimeField::convertToUserFormat($this->_latestExtensionData["expired_on"]).". You don't get any updates after this date.";
            }

            if($output && $this->_latestExtensionData["result"] == "error") {
                echo "<span style='color:red;font-weight:bold;'>ERROR:</span> ".$this->_latestExtensionData["error"]."";
            }
            if($output && $this->_latestExtensionData["result"] == "ext-notfound") {
                echo "<span style='color:red;font-weight:bold;'>ERROR:</span> Extension ".$this->_extension." for vtigerCRM ".$vtiger_current_version." not found.";
            }

            return ($this->_latestExtensionData["version"]);
        }

        public function installCurrentVersion() {
            $this->connect();
            $updateURL = $this->getUpdateUrl();

            $filename = sys_get_temp_dir()."/autoupdater.".md5($updateURL).".zip";

            global $root_directory;
            if(!is_writeable(sys_get_temp_dir()) && is_writeable($root_directory."/test/")) {
                $filename = $root_directory."/test/autoupdater.zip";
            }
            if(!is_writeable(sys_get_temp_dir()) && !is_writeable($root_directory."/test/")) {
                echo "<strong style='color:red;'>ERROR</strong> - You need to make the <b>test</b> directory inside vtiger root writable for webserver user!";return;
            }

            $data = $this->_client->call("downloadVersion", array(base64_encode($updateURL), sha1(base64_encode($updateURL)."ASD,.2#*")));
            if(!empty($_REQUEST["stefanDebug"])) {
                /* ONLY DEBUG*/ echo "<pre>";var_dump($this->_client->debug_str);
            }

            $data = base64_decode($data);

            file_put_contents($filename, $data);

            $package = new \Vtiger_Package();
            $package->update(\Vtiger_Module::getInstance($this->_extension), $filename);
        }

    }
