<?php
/**
 * Created by PhpStorm.
 * User: Stefan
 * Date: 04.12.2016
 * Time: 10:37
 */
$adb = \PearDatabase::getInstance();

if(!\SwVtTools\VtUtils::existTable("vtiger_tools_reltab")) {
    echo "Create table vtiger_tools_reltab  ... ok<br>";
    $adb->query("CREATE TABLE `vtiger_tools_reltab` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modulename` varchar(48) NOT NULL,
  `relations` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;");
}
if(!\SwVtTools\VtUtils::existTable("vtiger_tools_detailpart")) {
    echo "Create table vtiger_tools_detailpart  ... ok<br>";
    $adb->query("CREATE TABLE `vtiger_tools_detailpart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modulename` varchar(48) NOT NULL,
  `sort` tinyint(4) NOT NULL,
  `title` varchar(64) NOT NULL,
  `blockids` varchar(80) NOT NULL,
  `active` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;");
}
if(!\SwVtTools\VtUtils::existTable("vtiger_tools_listwidget")) {
    echo "Create table vtiger_tools_listwidget  ... ok<br>";
    $adb->query("CREATE TABLE `vtiger_tools_listwidget` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `active` tinyint(4) NOT NULL,
  `title` varchar(64) NOT NULL,
  `module` varchar(32) NOT NULL,
  `settings` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `module` (`module`)
) ENGINE=InnoDB;");
}
if(!\SwVtTools\VtUtils::existTable("vtiger_tools_sidebar")) {
    echo "Create table vtiger_tools_sidebar ... ok<br>";
    $adb->query("CREATE TABLE IF NOT EXISTS `vtiger_tools_sidebar` (
              `id` mediumint(8) unsigned NOT NULL,
              `active` TINYINT(1) NOT NULL,
              `tabid` mediumint(8) NOT NULL,
              `content` TEXT NOT NULL,
              `title` VARCHAR(128) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB;");
}
if(!\SwVtTools\VtUtils::existTable("vtiger_tools_logs")) {
    echo "Create table vtiger_tools_logs ... ok<br>";
    $adb->query("CREATE TABLE `vtiger_tools_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(8) NOT NULL,
  `log` text NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;");
}
if(!\SwVtTools\VtUtils::existTable("vtiger_tools_sidebar_seq")) {
    echo "Create table vtiger_tools_sidebar_seq ... ok<br>";
    $adb->query("CREATE TABLE IF NOT EXISTS `vtiger_tools_sidebar_seq` (
              `id` mediumint(8) unsigned NOT NULL
            ) ENGINE=InnoDB;");

    $adb->query("INSERT INTO vtiger_tools_sidebar_seq SET id = 1");
}

if(!\SwVtTools\VtUtils::existTable("vtiger_tools_referencefilter")) {
    echo "Create table vtiger_tools_referencefilter ... ok<br>";
    $adb->query("CREATE TABLE IF NOT EXISTS `vtiger_tools_referencefilter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modulename` varchar(48) NOT NULL,
  `field` varchar(48) NOT NULL,
  `condition` text NOT NULL,
  `tomodule` varchar(48) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `modulename` (`modulename`,`field`,`tomodule`)
) ENGINE=InnoDB;");
}