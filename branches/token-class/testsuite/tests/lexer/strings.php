<?php
require_once(dirname(__FILE__) . "/../../../classes/lexer.php");
require_once(dirname(__FILE__) . "/../../test-more.php");

$lexer = new PHPSQLLexer();

$sql = 'SELECT _utf8"haha", _latin"hohoho" From test t';
$s = $lexer->split($sql);
print_r($s);
$expected = getExpectedValue(dirname(__FILE__), 'charsets.serialized');
eq_array($s, $expected, 'some charsets');
