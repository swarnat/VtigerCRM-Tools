<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Stefan Warnat <support@stefanwarnat.de>
 * Date: 11.01.14 17:04
 * You must not use this file without permission.
 */
global $root_directory;
require_once($root_directory."/modules/".basename(dirname(dirname(__FILE__)))."/autoloader.php");

class Settings_SwVtTools_Upgrade_View extends Settings_Vtiger_Index_View {
    public function process(Vtiger_Request $request) {

        $moduleName = $request->getModule();

        $className = '\\'.$moduleName.'\\Autoload';
        $className::registerDirectory("~/modules/".$moduleName."/lib");

        $className = '\\'.$moduleName.'\\AutoUpdate';
        $objUpdater = new $className($moduleName, "stable");
        $step = $request->get("step");
        if(empty($step)) $step = 1;
        global $vtiger_current_version;

        if($step == 3) {
            $objUpdater->installCurrentVersion();

            echo "<div style='text-align:center;font-weight:bold;color:#2F8A25;'>".$moduleName." sucessfully updated</div>";
            exit();
        }

?>
    <div class="listViewPageDiv">
    	<div class="listViewTopMenuDiv">
            <div class="row-fluid">
        		<label class=" pull-left themeTextColor font-x-x-large">Upgrade <?php echo $moduleName ?> in vtigerCRM <?php echo $vtiger_current_version ?> | Step <?php echo $step; ?></label>
            </div>
            <hr>
    		<div class="clearfix"></div>
    	</div>
    	<div class="listViewContentDiv" id="listViewContents">
            <?php
                if($step == 1) {
                    $currentVersion = $objUpdater->getCurrentInstalledVersion();
                    $latestVersion = $objUpdater->getLatestVersion();

                    $licenseHint = false;
                    if(is_array($latestVersion)) {
                        $licenseHint = $latestVersion[1];
                        $latestVersion = $latestVersion[0];
                    }
                    echo "<div style='font-size:15px;'>Current installed version: ".$currentVersion."</div>";
                    echo "<div style='font-size:15px;'>Current available version: ".$latestVersion."</div>";

                    if($latestVersion > $currentVersion) {
                        $changelog = $objUpdater->getChangelog();
                        echo "<div style='font-weight:bold;margin-top:25px;'>Update available".(!empty($changelog)?" | <a href='".$objUpdater->getChangelog()."' target='blank'>see Changelog</a>":"")."</div>";

                        $upgradeUrl = "index.php?module=".$request->get("module")."&view=".$request->get("view")."&step=2";
                        $parent = $request->get("parent");
                        if(!empty($parent)) {
                            $upgradeUrl .= "&parent=".$parent;
                        }
                        $stefanDebug = $request->get("stefanDebug");
                        if(!empty($stefanDebug)) {
                            $upgradeUrl .= "&stefanDebug=1";
                        }

                        echo "<br><button class='btn addButton' onclick=\"window.location.href='".$upgradeUrl."';\"><strong>Install update</strong></button>";
                    }
                }

            If($step == 2) {
                $upgradeUrl = "index.php?module=".$request->get("module")."&view=".$request->get("view")."&step=3";
                $parent = $request->get("parent");
                if(!empty($parent)) {
                    $upgradeUrl .= "&parent=".$parent;
                }
                $stefanDebug = $request->get("stefanDebug");
                if(!empty($stefanDebug)) {
                    $upgradeUrl .= "&stefanDebug=1";
                }

                $latestVersion = $objUpdater->getLatestVersion();
                echo "<div style='font-weight:bold;margin-top:25px;'>Upgrade ".$moduleName." to ".$latestVersion."</div>";
                echo "<div id='pendingUpdate' style='text-align:center;'><img src='layouts/vlayout/skins/images/install_loading.gif'></div>";
                echo "<div id='updateLog'></div>";
                ?>
                <script type="text/javascript">
                    jQuery(function() {
                        AppConnector.request("<?php echo $upgradeUrl; ?>").then(function(data) {
                            jQuery("#pendingUpdate").hide();
                            jQuery("#updateLog").html(data);
                        });
                    });
                </script>
                <?php
            }
            ?>
<!--            -->
        </div>
    </div>
    <?
   	}

	public function getHeaderScripts(Vtiger_Request $request) {

	}

    public function checkPermission(Vtiger_Request $request) {

   	}
}