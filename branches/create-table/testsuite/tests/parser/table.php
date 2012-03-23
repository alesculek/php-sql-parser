<?php
require_once(dirname(__FILE__) . "/../../../php-sql-parser.php");
require_once(dirname(__FILE__) . "/../../test-more.php");

$parser = new PHPSQLParser();

$sql = "CREATE TABLE `prefix_quota` (
  `id` int(11) NOT NULL auto_increment,
  `sid` int(11) default NULL,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `qlimit` int(8) default NULL,
  `action` int(2) default NULL,
  `active` int(1) NOT NULL default '1',
  `autoload_url` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
)";
$p = $parser->parse($sql);
print_r($p);


$sql = "CREATE TABLE `prefix_sessions`(
      sesskey VARCHAR( 64 ) NOT NULL DEFAULT '',
        expiry DATETIME NOT NULL ,
      expireref VARCHAR( 250 ) DEFAULT '',
      created DATETIME NOT NULL ,
      modified DATETIME NOT NULL ,
      sessdata LONGTEXT,
      PRIMARY KEY ( sesskey ) ,
      INDEX sess2_expiry( expiry ),
      INDEX sess2_expireref( expireref )
)";
$p = $parser->parse($sql);
print_r($p);
