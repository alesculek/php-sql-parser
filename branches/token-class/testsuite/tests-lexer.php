<?php

/**
 * execute all tests
 */
$start = microtime(true);
require_once(dirname(__FILE__) . '/tests/lexer/strings.php');
require_once(dirname(__FILE__) . '/tests/lexer/parenthesis.php');
require_once(dirname(__FILE__) . '/tests/lexer/comments.php');
echo "processing tests within: " .  (microtime(true) - $start) . " seconds\n";