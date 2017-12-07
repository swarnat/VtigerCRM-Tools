<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class SwVtTools_Module_Model extends Products_Module_Model {

    public $test = "1";
    private $blockCache = false;
	/**
	 * Function to get list view query for popup window
	 * @param <String> $sourceModule Parent module
	 * @param <String> $field parent fieldname
	 * @param <Integer> $record parent id
	 * @param <String> $listQuery
	 * @return <String> Listview Query
	 */
	public function getQueryByModuleField($sourceModule, $field, $record, $listQuery) {
		$supportedModulesList = array('Leads', 'Accounts', 'HelpDesk', 'Potentials');
		if (($sourceModule == 'PriceBooks' && $field == 'priceBookRelatedList')
				|| in_array($sourceModule, $supportedModulesList)
				|| in_array($sourceModule, getInventoryModules())) {

			$condition = " vtiger_service.discontinued = 1 ";

			if ($sourceModule == 'PriceBooks' && $field == 'priceBookRelatedList') {
				$condition .= " AND vtiger_service.serviceid NOT IN (SELECT productid FROM vtiger_pricebookproductrel WHERE pricebookid = '$record') ";
			} elseif (in_array($sourceModule, $supportedModulesList)) {
				$condition .= " AND vtiger_service.serviceid NOT IN (SELECT relcrmid FROM vtiger_crmentityrel WHERE crmid = '$record' UNION SELECT crmid FROM vtiger_crmentityrel WHERE relcrmid = '$record') ";
			}

			$pos = stripos($listQuery, 'where');
			if ($pos) {
				$split = spliti('where', $listQuery);
				$overRideQuery = $split[0] . ' WHERE ' . $split[1] . ' AND ' . $condition;
			} else {
				$overRideQuery = $listQuery . ' WHERE ' . $condition;
			}
			return $overRideQuery;
		}
	}

	public static function Log($type, $content) {

		$content = str_replace('<br />', PHP_EOL, $content);
		$content = str_replace('SMTP -> get_lines(): $str is', '', $content);
		$content = str_replace('SMTP -> get_lines(): $str was', '', $content);
		$content = str_replace('SMTP -> get_lines(): $data is "', '', $content);
		$content = str_replace('SMTP -> get_lines(): $data was "', '', $content);
		$content = preg_replace("/\s(.{60,})\s/m", " ", $content); // Remove Attached Files

		$content = preg_replace("/CLIENT -> SMTP:(\s+)\n/", "", $content);

		$lines = explode("\n", $content);
		$debug = array();
		foreach($lines as $line) {
			$line = trim($line, '"');
			$line = str_replace(' ', '||', $line);
			$line = preg_replace("/\\s+/iu","",$line);
			$line = str_replace('||"', '', $line);
			$line = str_replace('||', ' ', $line);

			if(!empty($line)) {
				$debug[] = $line;
			}
		}

		$adb = \PearDatabase::getInstance();
		$sql = 'INSERT INTO vtiger_tools_logs SET `type` = ?, `log` = ?, created = NOW()';
		$adb->pquery($sql, array($type, implode(PHP_EOL, $debug)));
	}

    public function getBlockIndex($tabid, $blocklabel, $view = "detailview") {
        $db = PearDatabase::getInstance();

        if($this->blockCache === false) {
            $sql = "SELECT blockid, vtiger_blocks.tabid, blocklabel FROM vtiger_blocks INNER JOIN vtiger_field ON (vtiger_field.block = blockid AND vtiger_field.presence != 1) WHERE vtiger_blocks.tabid = ? AND detail_view = 0 GROUP BY blockid ORDER BY vtiger_blocks.sequence";
            $result = $db->pquery($sql, array($tabid), true);

            $this->blockCache = array();
            while($row = $db->fetchByAssoc($result)) {
                $this->blockCache[] = $row;
            }
        }

        foreach($this->blockCache as $index => $block) {
            if($blocklabel == $block["blocklabel"]) {
                return $index+1;
            }
        }

        return false;
    }

}