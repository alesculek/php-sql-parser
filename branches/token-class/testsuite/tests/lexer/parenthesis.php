<?php
require_once(dirname(__FILE__) . "/../../../classes/lexer.php");
require_once(dirname(__FILE__) . "/../../test-more.php");

$lexer = new PHPSQLLexer();

$sql = 'SELECT (colA * colB), ("a" || "(bcd") From test t';
$s = $lexer->split($sql);
$expected = getExpectedValue(dirname(__FILE__), 'parenthesis.serialized');
eq_array($s, $expected, 'some parenthesis');
