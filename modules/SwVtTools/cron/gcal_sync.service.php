<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Stefan Warnat <support@stefanwarnat.de>
 * Date: 31.10.14 13:06
 * You must not use this file without permission.
 */
require_once('modules/SwVtTools/lib/SwVtTools/VtUtils.php');

$adb = \PearDatabase::getInstance();

if(empty($current_language))
	$current_language = 'en_us';
$app_strings = return_application_language($current_language);

$sql = 'SELECT * FROM vtiger_google_sync WHERE googlemodule = "Calendar"';
$result = $adb->query($sql);

$oldCurrentUser2 = vglobal('current_user');

$enableSharedCalendar = false;
if(\SwVtTools\VtUtils::existTable('vtiger_gcal_sync')) {
    $enableSharedCalendar = true;
}
$listView = new Google_List_View();

echo 'GoogleCal Sync'.PHP_EOL;

    while($user = $adb->fetchByAssoc($result)) {
        try {
            $user3 = Users_Record_Model::getInstanceFromPreferenceFile($user['user']);

            vglobal('current_user', $user3);

            $controller = new Google_Calendar_Controller($user3);

            if($enableSharedCalendar === true && method_exists($controller, 'getCalendarId')) {
                $calId = $controller->getCalendarId();
                $controller->setCalendarId($calId);
            }
            if($enableSharedCalendar === true && !method_exists($controller, 'getCalendarId')) {
                $enableSharedCalendar = false;
            }

            $records = $controller->synchronize();
            $recordCount = $listView->getSyncRecordsCount($records);
            echo 'UserID '.$user['user'].PHP_EOL;
            echo 'VtigerCRM: '.str_pad($recordCount['vtiger']['update'], 5, ' ', STR_PAD_LEFT).' Update '.str_pad($recordCount['vtiger']['create'], 5, ' ', STR_PAD_LEFT).' Create '.str_pad($recordCount['vtiger']['delete'], 5, ' ', STR_PAD_LEFT).' Delete '.PHP_EOL;
            echo 'Google   : '.str_pad($recordCount['google']['update'], 5, ' ', STR_PAD_LEFT).' Update '.str_pad($recordCount['google']['create'], 5, ' ', STR_PAD_LEFT).' Create '.str_pad($recordCount['google']['delete'], 5, ' ', STR_PAD_LEFT).' Delete '.PHP_EOL;
        } catch (\Exception $exp) {
            echo 'Error UserID '.$user['user'].PHP_EOL;
            echo $exp->getMessage();
        }
    }


vglobal('current_user', $oldCurrentUser2);