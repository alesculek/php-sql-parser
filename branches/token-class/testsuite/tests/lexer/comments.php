<?php
require_once(dirname(__FILE__) . "/../../../classes/lexer.php");
require_once(dirname(__FILE__) . "/../../test-more.php");

$lexer = new PHPSQLLexer();

$sql = "SELECT --comment
* 
FROM 
-- comment
table";
$s = $lexer->split($sql);
print_r($s);
$expected = getExpectedValue(dirname(__FILE__), 'parenthesis.serialized');
eq_array($s, $expected, 'some inline comments');


$sql = "insert /* +APPEND */ into TableName (Col1,col2) values(1,'pol')";
$s = $lexer->split($sql);
print_r($s);
$expected = getExpectedValue(dirname(__FILE__), 'parenthesis.serialized');
eq_array($s, $expected, 'a multiline comment');
