<?php
/**
 * PHPSQLParser
 *
 * A pure PHP SQL (non validating) parser w/ focus on MySQL dialect of SQL
 *
 * Copyright (c) 2010-2012, Justin Swanhart
 * with contributions by André Rothe <arothe@phosco.info, phosco@gmx.de>
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 *   * Redistributions of source code must retain the above copyright notice,
 *     this list of conditions and the following disclaimer.
 *   * Redistributions in binary form must reproduce the above copyright notice,
 *     this list of conditions and the following disclaimer in the documentation
 *     and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT
 * SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR
 * BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 */

if (!defined('HAVE_PHP_SQL_PARSER')) {

    /**
     * This class implements some helper functions.
     * @author arothe
     *
     */
    class PHPSQLParserUtils {

        public function __construct() {
        }

        /**
         * Prints an array only if debug mode is on.
         * @param array $s
         * @param boolean $return, if true, the formatted array is returned via return parameter
         */
        protected function preprint($arr, $return = false) {
            $x = "<pre>";
            $x .= print_r($arr, 1);
            $x .= "</pre>";
            if ($return) {
                return $x;
            } else {
                if (isset($_ENV['DEBUG'])) {
                    print $x . "\n";
                }
            }
        }

        /**
         * Starts one of the strings within the given array $haystack with the string $needle?
         * @param string or array $haystack
         * @param string $needle
         */
        protected function startsWith($haystack, $needle) {
            if (is_string($needle)) {
                $needle = array($needle);
            }
            for ($j = 0; $j < count($needle); ++$j) {
                $length = strlen($needle[$j]);
                if (substr($haystack, 0, $length) === $needle[$j]) {
                    return $j;
                }
            }
            return false;
        }

        /**
         * Ends the given string $haystack with the string $needle?
         * @param string $haystack
         * @param string $needle
         */
        protected function endsWith($haystack, $needle) {
            $length = strlen($needle);
            if ($length == 0) {
                return true;
            }

            $start = $length * -1;
            return (substr($haystack, $start) === $needle);
        }

        protected function removeParenthesisFromStart($token) {

            $parenthesisRemoved = 0;

            $trim = trim($token);
            if ($trim !== "" && $trim[0] === "(") { // remove only one parenthesis pair now!
                $parenthesisRemoved++;
                $trim[0] = " ";
                $trim = trim($trim);
            }

            $parenthesis = $parenthesisRemoved;
            $i = 0;
            $string = 0;
            while ($i < strlen($trim)) {

                if ($trim[$i] === "\\") {
                    $i += 2; # an escape character, the next character is irrelevant
                    continue;
                }

                if (in_array($trim[$i], array("'", '"'))) {
                    $string++;
                }

                if (($string % 2 === 0) && ($trim[$i] === "(")) {
                    $parenthesis++;
                }

                if (($string % 2 === 0) && ($trim[$i] === ")")) {
                    if ($parenthesis == $parenthesisRemoved) {
                        $trim[$i] = " ";
                        $parenthesisRemoved--;
                    }
                    $parenthesis--;
                }
                $i++;
            }
            return trim($trim);
        }
    }
    
    class PHPSQLParserConstants extends PHPSQLParserUtils {

        const COLREF = "colref";
        const OPERATOR = "operator";
        const FUNC = "function";
        const AGG_FUNC = "aggregate_function";
        const CONSTANT = "const";
        const SIGN = "sign";
        const SUBQUERY = "subquery";
        const EXPRESSION = "expression";
        const MATCH = "match-arguments";
        const INLIST = "in-list";
        const RESERVED = "reserved";

        protected $reserved;
        protected $functions;

        public function __construct() {
            parent::__construct();
            $this->reserved = $this->getReservedWords();
            $this->functions = $this->getFunctionWords();
        }

        protected function getFunctionWords() {
            $result = array('abs', 'acos', 'adddate', 'addtime', 'aes_encrypt', 'aes_decrypt', 'against', 'ascii',
                            'asin', 'atan', 'avg', 'benchmark', 'bin', 'bit_and', 'bit_or', 'bitcount', 'bitlength',
                            'cast', 'ceiling', 'char', 'char_length', 'character_length', 'charset', 'coalesce',
                            'coercibility', 'collation', 'compress', 'concat', 'concat_ws', 'conection_id', 'conv',
                            'convert', 'convert_tz', 'cos', 'cot', 'count', 'crc32', 'curdate', 'current_user',
                            'currval', 'curtime', 'database', 'date_add', 'date_diff', 'date_format', 'date_sub',
                            'day', 'dayname', 'dayofmonth', 'dayofweek', 'dayofyear', 'decode', 'default', 'degrees',
                            'des_decrypt', 'des_encrypt', 'elt', 'encode', 'encrypt', 'exp', 'export_set', 'extract',
                            'field', 'find_in_set', 'floor', 'format', 'found_rows', 'from_days', 'from_unixtime',
                            'get_format', 'get_lock', 'group_concat', 'greatest', 'hex', 'hour', 'if', 'ifnull', 'in',
                            'inet_aton', 'inet_ntoa', 'insert', 'instr', 'interval', 'is_free_lock', 'is_used_lock',
                            'last_day', 'last_insert_id', 'lcase', 'least', 'left', 'length', 'ln', 'load_file',
                            'localtime', 'localtimestamp', 'locate', 'log', 'log2', 'log10', 'lower', 'lpad', 'ltrim',
                            'make_set', 'makedate', 'maketime', 'master_pos_wait', 'match', 'max', 'md5',
                            'microsecond', 'mid', 'min', 'minute', 'mod', 'month', 'monthname', 'nextval', 'now',
                            'nullif', 'oct', 'octet_length', 'old_password', 'ord', 'password', 'period_add',
                            'period_diff', 'pi', 'position', 'pow', 'power', 'quarter', 'quote', 'radians', 'rand',
                            'release_lock', 'repeat', 'replace', 'reverse', 'right', 'round', 'row_count', 'rpad',
                            'rtrim', 'sec_to_time', 'second', 'session_user', 'sha', 'sha1', 'sign', 'soundex',
                            'space', 'sqrt', 'std', 'stddev', 'stddev_pop', 'stddev_samp', 'strcmp', 'str_to_date',
                            'subdate', 'substring', 'substring_index', 'subtime', 'sum', 'sysdate', 'system_user',
                            'tan', 'time', 'timediff', 'timestamp', 'timestampadd', 'timestampdiff', 'time_format',
                            'time_to_sec', 'to_days', 'trim', 'truncate', 'ucase', 'uncompress', 'uncompressed_length',
                            'unhex', 'unix_timestamp', 'upper', 'user', 'utc_date', 'utc_time', 'utc_timestamp',
                            'uuid', 'var_pop', 'var_samp', 'variance', 'version', 'week', 'weekday', 'weekofyear',
                            'year', 'yearweek');

            return $this->changeCaseForArrayValues($result, CASE_UPPER);
        }

        protected function getReservedWords() {
            /* includes functions */
            $result = array('abs', 'acos', 'adddate', 'addtime', 'aes_encrypt', 'aes_decrypt', 'against', 'ascii',
                            'asin', 'atan', 'avg', 'benchmark', 'bin', 'bit_and', 'bit_or', 'bitcount', 'bitlength',
                            'cast', 'ceiling', 'char', 'char_length', 'character_length', 'charset', 'coalesce',
                            'coercibility', 'collation', 'compress', 'concat', 'concat_ws', 'conection_id', 'conv',
                            'convert', 'convert_tz', 'cos', 'cot', 'count', 'crc32', 'curdate', 'current_user',
                            'currval', 'curtime', 'database', 'date_add', 'date_diff', 'date_format', 'date_sub',
                            'day', 'dayname', 'dayofmonth', 'dayofweek', 'dayofyear', 'decode', 'default', 'degrees',
                            'des_decrypt', 'des_encrypt', 'elt', 'encode', 'encrypt', 'exp', 'export_set', 'extract',
                            'field', 'find_in_set', 'floor', 'format', 'found_rows', 'from_days', 'from_unixtime',
                            'get_format', 'get_lock', 'group_concat', 'greatest', 'hex', 'hour', 'if', 'ifnull', 'in',
                            'inet_aton', 'inet_ntoa', 'insert', 'instr', 'interval', 'is_free_lock', 'is_used_lock',
                            'last_day', 'last_insert_id', 'lcase', 'least', 'left', 'length', 'ln', 'load_file',
                            'localtime', 'localtimestamp', 'locate', 'log', 'log2', 'log10', 'lower', 'lpad', 'ltrim',
                            'make_set', 'makedate', 'maketime', 'master_pos_wait', 'match', 'max', 'md5',
                            'microsecond', 'mid', 'min', 'minute', 'mod', 'month', 'monthname', 'nextval', 'now',
                            'nullif', 'oct', 'octet_length', 'old_password', 'ord', 'password', 'period_add',
                            'period_diff', 'pi', 'position', 'pow', 'power', 'quarter', 'quote', 'radians', 'rand',
                            'release_lock', 'repeat', 'replace', 'reverse', 'right', 'round', 'row_count', 'rpad',
                            'rtrim', 'sec_to_time', 'second', 'session_user', 'sha', 'sha1', 'sign', 'soundex',
                            'space', 'sqrt', 'std', 'stddev', 'stddev_pop', 'stddev_samp', 'strcmp', 'str_to_date',
                            'subdate', 'substring', 'substring_index', 'subtime', 'sum', 'sysdate', 'system_user',
                            'tan', 'time', 'timediff', 'timestamp', 'timestampadd', 'timestampdiff', 'time_format',
                            'time_to_sec', 'to_days', 'trim', 'truncate', 'ucase', 'uncompress', 'uncompressed_length',
                            'unhex', 'unix_timestamp', 'upper', 'user', 'utc_date', 'utc_time', 'utc_timestamp',
                            'uuid', 'var_pop', 'var_samp', 'variance', 'version', 'week', 'weekday', 'weekofyear',
                            'year', 'yearweek', 'add', 'all', 'alter', 'analyze', 'and', 'as', 'asc', 'asensitive',
                            'auto_increment', 'bdb', 'before', 'berkeleydb', 'between', 'bigint', 'binary', 'blob',
                            'both', 'by', 'call', 'cascade', 'case', 'change', 'char', 'character', 'check', 'collate',
                            'column', 'columns', 'condition', 'connection', 'constraint', 'continue', 'create',
                            'cross', 'current_date', 'current_time', 'current_timestamp', 'cursor', 'database',
                            'databases', 'day_hour', 'day_microsecond', 'day_minute', 'day_second', 'dec', 'decimal',
                            'declare', 'default', 'delayed', 'delete', 'desc', 'describe', 'deterministic', 'distinct',
                            'distinctrow', 'div', 'double', 'drop', 'else', 'elseif', 'end', 'enclosed', 'escaped',
                            'exists', 'exit', 'explain', 'false', 'fetch', 'fields', 'float', 'for', 'force',
                            'foreign', 'found', 'frac_second', 'from', 'fulltext', 'grant', 'group', 'having',
                            'high_priority', 'hour_microsecond', 'hour_minute', 'hour_second', 'if', 'ignore', 'in',
                            'index', 'infile', 'inner', 'innodb', 'inout', 'insensitive', 'insert', 'int', 'integer',
                            'interval', 'into', 'io_thread', 'is', 'iterate', 'join', 'key', 'keys', 'kill', 'leading',
                            'leave', 'left', 'like', 'limit', 'lines', 'load', 'localtime', 'localtimestamp', 'lock',
                            'long', 'longblob', 'longtext', 'loop', 'low_priority', 'master_server_id', 'match',
                            'mediumblob', 'mediumint', 'mediumtext', 'middleint', 'minute_microsecond',
                            'minute_second', 'mod', 'natural', 'not', 'no_write_to_binlog', 'null', 'numeric', 'on',
                            'optimize', 'option', 'optionally', 'or', 'order', 'out', 'outer', 'outfile', 'precision',
                            'primary', 'privileges', 'procedure', 'purge', 'read', 'real', 'references', 'regexp',
                            'rename', 'repeat', 'replace', 'require', 'restrict', 'return', 'revoke', 'right', 'rlike',
                            'second_microsecond', 'select', 'sensitive', 'separator', 'set', 'show', 'smallint',
                            'some', 'soname', 'spatial', 'specific', 'sql', 'sqlexception', 'sqlstate', 'sqlwarning',
                            'sql_big_result', 'sql_calc_found_rows', 'sql_small_result', 'sql_tsi_day',
                            'sql_tsi_frac_second', 'sql_tsi_hour', 'sql_tsi_minute', 'sql_tsi_month',
                            'sql_tsi_quarter', 'sql_tsi_second', 'sql_tsi_week', 'sql_tsi_year', 'ssl', 'starting',
                            'straight_join', 'striped', 'table', 'tables', 'terminated', 'then', 'timestampadd',
                            'timestampdiff', 'tinyblob', 'tinyint', 'tinytext', 'to', 'trailing', 'true', 'undo',
                            'union', 'unique', 'unlock', 'unsigned', 'update', 'usage', 'use', 'user_resources',
                            'using', 'utc_date', 'utc_time', 'utc_timestamp', 'values', 'varbinary', 'varchar',
                            'varcharacter', 'varying', 'when', 'where', 'while', 'with', 'write', 'xor', 'year_month',
                            'zerofill');

            return $this->changeCaseForArrayValues($result, CASE_UPPER);
        }

        /**
         * 
         * Change the case of the values of an array.
         * 
         * @param Array of Strings $arr
         * @param unknown_type $case (CASE_LOWER or CASE_UPPER)
         * @throws InvalidArgumentException if the first argument is not an array
         */
        protected function changeCaseForArrayValues($arr, $case) {
            if (!is_array($arr)) {
                throw new InvalidArgumentException("first argument must be an array");
            }
            for ($i = 0; $i < count($arr); ++$i) {
                if ($case == CASE_LOWER) {
                    $arr[$i] = strtolower($arr[$i]);
                }
                if ($case == CASE_UPPER) {
                    $arr[$i] = strtoupper($arr[$i]);
                }
            }
            return $arr;
        }
    }

    class PHPSQLParserInfo extends PHPSQLParserConstants {

        private $parser;
        private $lexer;
        public $processed;
        public $expr;
        public $key;
        public $token;
        public $tokenType;
        public $prevToken;
        public $prevTokenType;
        public $trim;
        public $upper;

        public function __construct($old = false) {
            parent::__construct();
            $this->parser = new PHPSQLParser();
            $this->lexer = new PHPSQLLexer();
            
            $this->processed = false;
            $this->expr = "";
            $this->key = false;
            $this->token = false;
            $this->tokenType = "";
            $this->prevToken = "";
            $this->prevTokenType = "";
            $this->trim = false;
            $this->upper = false;

            if ($old !== false) {
                $expr = $old->expr;
                $expr[] = array('expr_type' => $old->tokenType, 'base_expr' => $old->token,
                                'sub_tree' => $old->processed);
                $this->expr = $expr;
                $this->prevToken = $old->upper;
                $this->prevTokenType = $old->tokenType;
            }
        }

        private function setUpper($string) {
            $this->upper = strtoupper($string);
        }

        private function setTrim($string) {
            $this->trim = trim($string);
        }

        private function processParenthesisContent() {
            $tmptokens = $this->splitAndRemoveCommas($this->trim);
            $this->processed = $this->parser->process_expr_list($tmptokens);
            $last = array_pop($this->expr);
            $this->token = $last['base_expr'];
        }

        private function splitAndRemoveCommas($token) {
            $tmptokens = $this->lexer->split($this->removeParenthesisFromStart($token));
            foreach ($tmptokens as $k => $v) {
                if (trim($v) == ',') {
                    unset($tmptokens[$k]);
                }
            }
            return array_values($tmptokens);
        }

        public function setToken($key, $token) {
            $this->key = $key;
            $this->token = $token;
            $this->setTrim($token);
            $this->setUpper($token);
        }

        public function setTokenType($type) {
            $this->tokenType = $type;
        }

        public function isEmptyToken() {
            return ($this->trim === "");
        }

        public function isSubQuery() {
            return (preg_match("/^\\(\\s*SELECT/i", $this->trim));
        }

        /**
         * tokenize and parse the subquery.
         * we remove the enclosing parenthesis for the tokenizer
         */
        public function processAsSubQuery() {
            $this->processed = $this->parser->parse($this->removeParenthesisFromStart($this->trim));
            $this->tokenType = PHPSQLParserConstants::SUBQUERY;
        }

        public function isSurroundedByParenthesis() {
            return ($this->upper[0] === '(' && substr($this->upper, -1) === ')');
        }

        public function isPreviousTokenTypeEquals($type) {
            return $this->prevTokenType === $type;
        }

        public function isPreviousTokenEquals($token) {
            return $this->prevToken === $token;
        }

        public function processAsColRef() {
            $this->tokenType = PHPSQLParserConstants::FUNC;
            $this->processAsFunction();
        }
        public function processAsFunction() {
            $this->processParenthesisContent();
            $this->tokenType = $this->prevTokenType;
            $this->prevTokenType = $this->prevToken = "";
        }
        public function processAsAggregateFunction() {
            $this->processAsFunction();
        }

        public function processAsInList() {
            $tmptokens = $this->splitAndRemoveCommas($this->trim);
            $this->processed = $this->parser->process_expr_list($tmptokens);
            $this->tokenType = PHPSQLParserConstants::INLIST;
            $this->prevTokenType = $this->prevToken = "";
        }

        public function processAsAgainstArguments() {
            $tmptokens = $this->lexer->split($this->removeParenthesisFromStart($parseInfo->trim));
            if (count($tmptokens) > 1) {
                $match_mode = implode('', array_slice($tmptokens, 1));
                $parseInfo->processed = array($list[0], $match_mode);
            } else {
                $parseInfo->processed = $list[0];
            }
            $parseInfo->tokenType = PHPSQLParserConstants::MATCH;
            $parseInfo->prevTokenType = $parseInfo->prevToken = "";
        }

        public function processAsAsterisk() {

            # last token is colref, const or expression
            # it is an operator, in all other cases it is an all-columns-alias
            # if the previous colref ends with a dot, the * is the all-columns-alias
            if (!is_array($this->expr)) {
                $this->processed = false; #no subtree
                $this->tokenType = PHPSQLParserConstants::COLREF; # single or first element of select -> *
                return false;
            }

            $last = array_pop($this->expr);

            if ($last['expr_type'] === PHPSQLParserConstants::COLREF && substr($last['base_expr'], -1, 1) === ".") {
                $last['base_expr'] .= '*'; # tablealias dot *
                $this->expr[] = $last;
                $this->processed = false; #no subtree
                return true;
            }

            $this->expr[] = $last;

            if (!in_array($last['expr_type'],
                    array(PHPSQLParserConstants::COLREF, PHPSQLParserConstants::CONSTANT,
                            PHPSQLParserConstants::EXPRESSION))) {

                $this->processed = false; #no subtree
                $this->tokenType = PHPSQLParserConstants::COLREF;
                return false;
            }

            $this->processAsOperator();
            return false;
        }

        public function processAsOperator() {
            $this->processed = false;
            $this->tokenType = PHPSQLParserConstants::OPERATOR;
        }

        public function processAsSign() {
            $this->processAsOperator();

            if (!$this->isPreviousTokenTypeEquals(PHPSQLParserConstants::COLREF)
                    && !$this->isPreviousTokenTypeEquals(PHPSQLParserConstants::FUNC)
                    && !$this->isPreviousTokenTypeEquals(PHPSQLParserConstants::CONSTANT)
                    && !$this->isPreviousTokenTypeEquals(PHPSQLParserConstants::AGG_FUNC)
                    && !$this->isPreviousTokenTypeEquals(PHPSQLParserConstants::SUBQUERY)) {
                $this->tokenType = PHPSQLParserConstants::SIGN;
            }
        }

        public function processAsUnknown() {

            $this->processed = false; # no sub-tree
            switch ($this->token[0]) {
            case "'":
            case '"':
                $this->tokenType = PHPSQLParserConstants::CONSTANT;
                break;
            case '`':
                $this->tokenType = PHPSQLParserConstants::COLREF;
                break;

            default:
                if (is_numeric($this->token)) {
                    $this->tokenType = PHPSQLParserConstants::CONSTANT;

                    if ($this->isPreviousTokenTypeEquals(PHPSQLParserConstants::SIGN)) {
                        array_pop($this->expr); #remove last entry
                        $this->token = $this->prevToken . $this->token;
                    }

                } else {
                    $this->tokenType = PHPSQLParserConstants::COLREF;
                }
                break;
            }
        }

        public function isTokenTypeWithin($typeArray) {
            return in_array($this->tokenType, $typeArray);
        }

        public function isReservedWord() {
            return in_array($this->upper, $this->reserved);
        }

        public function isKnownFunction() {
            return in_array($this->upper, $this->functions);
        }

        public function isKnownAggregateFunction() {
            return in_array($this->upper,
                    array('AVG', 'SUM', 'COUNT', 'MIN', 'MAX', 'STDDEV', 'STDDEV_SAMP', 'STDDEV_POP', 'VARIANCE',
                            'VAR_SAMP', 'VAR_POP', 'GROUP_CONCAT', 'BIT_AND', 'BIT_OR', 'BIT_XOR'));
        }

        public function processAsKnownFunction() {
            if ($this->isKnownAggregateFunction()) {
                $this->tokenType = PHPSQLParserConstants::AGG_FUNC;
            } else {
                $this->tokenType = PHPSQLParserConstants::FUNC;
            }
        }

        public function processAsReservedWord() {
            $this->tokenType = PHPSQLParserConstants::RESERVED;
        }
    }


    /**
     * This class splits the SQL string into little parts, which the parser can
     * use to build the result array.
     * 
     * @author arothe
     *
     */
    class PHPSQLLexer extends PHPSQLParserUtils {

        public function __construct() {
            parent::__construct();
        }

        public function split($sql) {
            if (!is_string($sql)) {
                throw new InvalidParameterException($sql);
            }

            $tokens = array();
            $token = "";
            $splitter = array("\r\n", "!=", ">=", "<=", "<>", "\\", "&&", ">", "<", "|", "=", "^", "(", ")", "\t",
                              "\n", "'", "\"", "`", ",", "@", " ", "+", "-", "*", "/", ";");

            while (strlen($sql) > 0) {

                $idx = $this->startsWith($sql, $splitter);
                if ($idx === false) {
                    $token .= $sql[0];
                    $sql = substr($sql, 1);
                    continue;
                }

                if ($token !== "") {
                    $tokens[] = $token;
                }
                $tokens[] = $splitter[$idx];
                $sql = substr($sql, strlen($splitter[$idx]));
                $token = "";
            }

            if ($token !== "") {
                $tokens[] = $token;
            }

            $tokens = $this->concatEscapeSequences($tokens);
            $tokens = $this->balanceBackticks($tokens);
            $tokens = $this->concatColReferences($tokens);
            $tokens = $this->balanceParenthesis($tokens);
            return $tokens;
        }

        private function balanceBackticks($tokens) {
            $i = 0;
            $cnt = count($tokens);
            while ($i < $cnt) {

                if (!isset($tokens[$i])) {
                    $i++;
                    continue;
                }

                $token = $tokens[$i];

                if (in_array($token, array("'", "\"", "`"))) {
                    $tokens = $this->balanceCharacter($tokens, $i, $token);
                }

                $i++;
            }

            return $tokens;
        }

        # backticks are not balanced within one token, so we have
        # to re-combine some tokens
        private function balanceCharacter($tokens, $idx, $char) {

            $token_count = count($tokens);
            $i = $idx + 1;
            while ($i < $token_count) {

                if (!isset($tokens[$i])) {
                    $i++;
                    continue;
                }

                $token = $tokens[$i];
                $tokens[$idx] .= $token;
                unset($tokens[$i]);

                if ($token === $char) {
                    break;
                }

                $i++;
            }
            return array_values($tokens);
        }

        /*
         * does the token end with dot?
         * concat it with the next token(s)
         * 
         * does the token start with a dot?
         * concat it with the previous token(s)
         */
        private function concatColReferences($tokens) {

            $cnt = count($tokens);
            $i = 0;
            while ($i < $cnt) {

                if (!isset($tokens[$i])) {
                    $i++;
                    continue;
                }

                if ($this->startsWith($tokens[$i], '.') !== false) {

                    // concat the previous tokens, till the token has been changed
                    $k = $i - 1;
                    $len = strlen($tokens[$i]);
                    while (($k >= 0) && ($len == strlen($tokens[$i]))) {
                        $tokens[$i] = $tokens[$k] . $tokens[$i];
                        unset($tokens[$k]);
                        $k--;
                    }
                }

                if ($this->endsWith($tokens[$i], '.')) {

                    // concat the next tokens, till the token has been changed
                    $k = $i + 1;
                    $len = strlen($tokens[$i]);
                    while (($k < $cnt) && ($len == strlen($tokens[$i]))) {
                        $tokens[$i] .= $tokens[$k];
                        unset($tokens[$k]);
                        $k++;
                    }
                }

                $i++;
            }

            return array_values($tokens);
        }

        private function concatEscapeSequences($tokens) {
            $tokenCount = count($tokens);
            $i = 0;
            while ($i < $tokenCount) {

                if ($this->endsWith($tokens[$i], "\\")) {
                    $i++;
                    if (isset($tokens[$i])) {
                        $tokens[$i - 1] .= $tokens[$i];
                        unset($tokens[$i]);
                    }
                }
                $i++;
            }
            return array_values($tokens);
        }

        private function balanceParenthesis($tokens) {
            $token_count = count($tokens);
            $i = 0;
            while ($i < $token_count) {
                if ($tokens[$i] !== '(') {
                    $i++;
                    continue;
                }
                $count = 1;
                for ($n = $i + 1; $n < $token_count; $n++) {
                    $token = $tokens[$n];
                    if ($token === '(') {
                        $count++;
                    }
                    if ($token === ')') {
                        $count--;
                    }
                    $tokens[$i] .= $token;
                    unset($tokens[$n]);
                    if ($count === 0) {
                        $n++;
                        break;
                    }
                }
                $i = $n;
            }
            return array_values($tokens);
        }
    }

    /**
     * This class implements the parser functionality.
     * @author greenlion@gmail.com
     * @author arothe@phosco.info
     */
    class PHPSQLParser extends PHPSQLParserUtils {

        private $lexer;

        public function __construct($sql = false, $calcPositions = false) {
            parent::__construct();
            $this->lexer = new PHPSQLLexer();

            if ($sql) {
                $this->parse($sql, $calcPositions);
            }
        }

        public function parse($sql, $calcPositions = false) {
            #lex the SQL statement
            $inputArray = $this->split_sql($sql);

            #This is the highest level lexical analysis.  This is the part of the
            #code which finds UNION and UNION ALL query parts
            $queries = $this->processUnion($inputArray);

            # If there was no UNION or UNION ALL in the query, then the query is
            # stored at $queries[0].
            if (!$this->isUnion($queries)) {
                $queries = $this->processSQL($queries[0]);
            }

            # calc the positions of some important tokens
            if ($calcPositions) {
                $calculator = new PositionCalculator();
                $queries = $calculator->setPositionsWithinSQL($sql, $queries);
            }

            # store the parsed queries
            $this->parsed = $queries;
            return $this->parsed;
        }

        private function processUnion($inputArray) {
            $outputArray = array();

            #sometimes the parser needs to skip ahead until a particular
            #token is found
            $skipUntilToken = false;

            #This is the last type of union used (UNION or UNION ALL)
            #indicates a) presence of at least one union in this query
            #          b) the type of union if this is the first or last query
            $unionType = false;

            #Sometimes a "query" consists of more than one query (like a UNION query)
            #this array holds all the queries
            $queries = array();

            foreach ($inputArray as $key => $token) {
                $trim = trim($token);

                # overread all tokens till that given token
                if ($skipUntilToken) {
                    if ($trim === "") {
                        continue; # read the next token
                    }
                    if (strtoupper($trim) === $skipUntilToken) {
                        $skipUntilToken = false;
                        continue; # read the next token
                    }
                }

                if (strtoupper($trim) !== "UNION") {
                    $outputArray[] = $token; # here we get empty tokens, if we remove these, we get problems in parse_sql()
                    continue;
                }

                $unionType = "UNION";

                # we are looking for an ALL token right after UNION
                for ($i = $key + 1; $i < count($inputArray); ++$i) {
                    if (trim($inputArray[$i]) === "") {
                        continue;
                    }
                    if (strtoupper($inputArray[$i]) !== "ALL") {
                        break;
                    }
                    # the other for-loop should overread till "ALL"
                    $skipUntilToken = "ALL";
                    $unionType = "UNION ALL";
                }

                # store the tokens related to the unionType
                $queries[$unionType][] = $outputArray;
                $outputArray = array();
            }

            # the query tokens after the last UNION or UNION ALL
            # or we don't have an UNION/UNION ALL
            if (!empty($outputArray)) {
                if ($unionType) {
                    $queries[$unionType][] = $outputArray;
                } else {
                    $queries[] = $outputArray;
                }
            }

            return $this->processMySQLUnion($queries);
        }

        /** MySQL supports a special form of UNION:
         * (select ...)
         * union
         * (select ...)
         *
         * This function handles this query syntax.  Only one such subquery
         * is supported in each UNION block.  (select)(select)union(select) is not legal.
         * The extra queries will be silently ignored.
         */
        private function processMySQLUnion($queries) {
            $unionTypes = array('UNION', 'UNION ALL');
            foreach ($unionTypes as $unionType) {

                if (empty($queries[$unionType])) {
                    continue;
                }

                foreach ($queries[$unionType] as $key => $tokenList) {
                    foreach ($tokenList as $z => $token) {
                        $token = trim($token);
                        if ($token === "") {
                            continue;
                        }

                        # starts with "(select"
                        if (preg_match("/^\\(\\s*select\\s*/i", $token)) {
                            $queries[$unionType][$key] = $this->parse($this->removeParenthesisFromStart($token));
                            break;
                        }

                        $queries[$unionType][$key] = $this->processSQL($queries[$unionType][$key]);
                        break;
                    }
                }
            }
            # it can be parsed or not
            return $queries;
        }

        private function isUnion($queries) {
            $unionTypes = array('UNION', 'UNION ALL');
            foreach ($unionTypes as $unionType) {
                if (!empty($queries[$unionType])) {
                    return true;
                }
            }
            return false;
        }

        #this function splits up a SQL statement into easy to "parse"
        #tokens for the SQL processor
        private function split_sql($sql) {
            return $this->lexer->split($sql);
        }

        /* This function breaks up the SQL statement into logical sections.
         Some sections are then further handled by specialized functions.
         */
        private function processSQL(&$tokens) {
            $prev_category = "";
            $token_category = "";
            $skip_next = false;
            $out = false;

            $tokenCount = count($tokens);
            for ($tokenNumber = 0; $tokenNumber < $tokenCount; ++$tokenNumber) {

                $token = $tokens[$tokenNumber];
                $trim = trim($token); # this removes also \n and \t!

                # if it starts with an "(", it should follow a SELECT
                if ($trim !== "" && $trim[0] == "(" && $token_category == "") {
                    $token_category = 'SELECT';
                }

                /* If it isn't obvious, when $skip_next is set, then we ignore the next real
                 token, that is we ignore whitespace.
                 */
                if ($skip_next) {
                    if ($trim === "") {
                        if ($token_category !== "") { # is this correct??
                            $out[$token_category][] = $token;
                        }
                        continue;
                    }
                    #to skip the token we replace it with whitespace
                    $trim = "";
                    $token = "";
                    $skip_next = false;
                }

                $upper = strtoupper($trim);
                switch ($upper) {

                /* Tokens that get their own sections. These keywords have subclauses. */
                case 'SELECT':
                case 'ORDER':
                case 'LIMIT':
                case 'SET':
                case 'DUPLICATE':
                case 'VALUES':
                case 'GROUP':
                case 'ORDER':
                case 'HAVING':
                case 'WHERE':
                case 'RENAME':
                case 'CALL':
                case 'PROCEDURE':
                case 'FUNCTION':
                case 'DATABASE':
                case 'SERVER':
                case 'LOGFILE':
                case 'DEFINER':
                case 'RETURNS':
                case 'EVENT':
                case 'TABLESPACE':
                case 'TRIGGER':
                case 'DATA':
                case 'DO':
                case 'PASSWORD':
                case 'PLUGIN':
                case 'FROM':
                case 'FLUSH':
                case 'KILL':
                case 'RESET':
                case 'START':
                case 'STOP':
                case 'PURGE':
                case 'EXECUTE':
                case 'PREPARE':
                case 'DEALLOCATE':
                    if ($trim == 'DEALLOCATE') {
                        $skip_next = true;
                    }
                    /* this FROM is different from FROM in other DML (not join related) */
                    if ($token_category == 'PREPARE' && $upper == 'FROM') {
                        continue 2;
                    }

                    $token_category = $upper;
                    break;

                case 'INTO':
                # prevent wrong handling of CACHE within LOAD INDEX INTO CACHE...
                    if ($prev_category === 'LOAD') {
                        $out[$prev_category][] = $upper;
                        continue 2;
                    }
                    $token_category = $upper;
                    break;

                case 'USER':
                # prevent wrong processing as keyword
                    if (in_array($prev_category, array('CREATE', 'RENAME', 'DROP'), true)) {
                        $token_category = $upper;
                    }
                    break;

                case 'VIEW':
                # prevent wrong processing as keyword
                    if (in_array($prev_category, array('CREATE', 'ALTER', 'DROP'), true)) {
                        $token_category = $upper;
                    }
                    break;

                /* These tokens get their own section, but have no subclauses.
                 These tokens identify the statement but have no specific subclauses of their own. */
                case 'DELETE':
                case 'ALTER':
                case 'INSERT':
                case 'REPLACE':
                case 'TRUNCATE':
                case 'CREATE':
                case 'TRUNCATE':
                case 'OPTIMIZE':
                case 'GRANT':
                case 'REVOKE':
                case 'SHOW':
                case 'HANDLER':
                case 'LOAD':
                case 'ROLLBACK':
                case 'SAVEPOINT':
                case 'UNLOCK':
                case 'INSTALL':
                case 'UNINSTALL':
                case 'ANALZYE':
                case 'BACKUP':
                case 'CHECK':
                case 'CHECKSUM':
                case 'REPAIR':
                case 'RESTORE':
                case 'DESCRIBE':
                case 'EXPLAIN':
                case 'USE':
                case 'HELP':
                    $token_category = $upper; /* set the category in case these get subclauses
                                              in a future version of MySQL */
                    $out[$upper][0] = $upper;
                    continue 2;
                    break;

                case 'CACHE':
                    if (($prev_category === "") || (in_array($prev_category, array('RESET', 'FLUSH', 'LOAD')))) {
                        $token_category = $upper;
                        continue 2;
                    }
                    break;

                /* This is either LOCK TABLES or SELECT ... LOCK IN SHARE MODE*/
                case 'LOCK':
                    if ($token_category == "") {
                        $token_category = $upper;
                        $out[$upper][0] = $upper;
                    } else {
                        $trim = 'LOCK IN SHARE MODE';
                        $skip_next = true;
                        $out['OPTIONS'][] = $trim;
                    }
                    continue 2;
                    break;

                case 'USING': /* USING in FROM clause is different from USING w/ prepared statement*/
                    if ($token_category == 'EXECUTE') {
                        $token_category = $upper;
                        continue 2;
                    }
                    if ($token_category == 'FROM' && !empty($out['DELETE'])) {
                        $token_category = $upper;
                        continue 2;
                    }
                    break;

                /* DROP TABLE is different from ALTER TABLE DROP ... */
                case 'DROP':
                    if ($token_category != 'ALTER') {
                        $token_category = $upper;
                        $out[$upper][0] = $upper;
                        continue 2;
                    }
                    break;

                case 'FOR':
                    $skip_next = true;
                    $out['OPTIONS'][] = 'FOR UPDATE';
                    continue 2;
                    break;

                case 'UPDATE':
                    if ($token_category == "") {
                        $token_category = $upper;
                        continue 2;

                    }
                    if ($token_category == 'DUPLICATE') {
                        continue 2;
                    }
                    break;

                case 'START':
                    $trim = "BEGIN";
                    $out[$upper][0] = $upper;
                    $skip_next = true;
                    break;

                /* These tokens are ignored. */
                case 'BY':
                case 'ALL':
                case 'SHARE':
                case 'MODE':
                case 'TO':
                case ';':
                    continue 2;
                    break;

                case 'KEY':
                    if ($token_category == 'DUPLICATE') {
                        continue 2;
                    }
                    break;

                /* These tokens set particular options for the statement.  They never stand alone.*/
                case 'DISTINCTROW':
                    $trim = 'DISTINCT';
                case 'DISTINCT':
                case 'HIGH_PRIORITY':
                case 'LOW_PRIORITY':
                case 'DELAYED':
                case 'IGNORE':
                case 'FORCE':
                case 'STRAIGHT_JOIN':
                case 'SQL_SMALL_RESULT':
                case 'SQL_BIG_RESULT':
                case 'QUICK':
                case 'SQL_BUFFER_RESULT':
                case 'SQL_CACHE':
                case 'SQL_NO_CACHE':
                case 'SQL_CALC_FOUND_ROWS':
                    $out['OPTIONS'][] = $upper;
                    continue 2;
                    break;

                case 'WITH':
                    if ($token_category == 'GROUP') {
                        $skip_next = true;
                        $out['OPTIONS'][] = 'WITH ROLLUP';
                        continue 2;
                    }
                    break;

                case 'AS':
                    break;

                case '':
                case ',':
                case ';':
                    break;

                default:
                    break;
                }

                # remove obsolete category after union (empty category because of
                # empty token before select)
                if ($token_category !== "" && ($prev_category === $token_category)) {
                    $out[$token_category][] = $token;
                }

                $prev_category = $token_category;
            }

            return $this->processSQLParts($out);
        }

        private function processSQLParts($out) {
            if (!$out) {
                return false;
            }
            if (!empty($out['SELECT'])) {
                $out['SELECT'] = $this->process_select($out['SELECT']);
            }
            if (!empty($out['FROM'])) {
                $out['FROM'] = $this->process_from($out['FROM']);
            }
            if (!empty($out['USING'])) {
                $out['USING'] = $this->process_from($out['USING']);
            }
            if (!empty($out['UPDATE'])) {
                $out['UPDATE'] = $this->process_from($out['UPDATE']);
            }
            if (!empty($out['GROUP'])) {
                $out['GROUP'] = $this->process_group($out['GROUP'], $out['SELECT']);
            }
            if (!empty($out['ORDER'])) {
                $out['ORDER'] = $this->process_order($out['ORDER'], $out['SELECT']);
            }
            if (!empty($out['LIMIT'])) {
                $out['LIMIT'] = $this->process_limit($out['LIMIT']);
            }
            if (!empty($out['WHERE'])) {
                $out['WHERE'] = $this->process_expr_list($out['WHERE']);
            }
            if (!empty($out['HAVING'])) {
                $out['HAVING'] = $this->process_expr_list($out['HAVING']);
            }
            if (!empty($out['SET'])) {
                $out['SET'] = $this->process_set_list($out['SET']);
            }
            if (!empty($out['DUPLICATE'])) {
                $out['ON DUPLICATE KEY UPDATE'] = $this->process_set_list($out['DUPLICATE']);
                unset($out['DUPLICATE']);
            }
            if (!empty($out['INSERT'])) {
                $out = $this->process_insert($out);
            }
            if (!empty($out['REPLACE'])) {
                $out = $this->process_insert($out, 'REPLACE');
            }
            if (!empty($out['DELETE'])) {
                $out = $this->process_delete($out);
            }
            if (!empty($out['VALUES'])) {
                $out = $this->process_values($out);
            }
            if (!empty($out['INTO'])) {
                $out = $this->process_into($out);
            }
            return $out;
        }

        /* A SET list is simply a list of key = value expressions separated by comma (,).
         This function produces a list of the key/value expressions.
         */
        private function getColumn($base_expr) {
            $column = $this->process_expr_list($this->split_sql($base_expr));
            return array('expr_type' => 'expression', 'base_expr' => trim($base_expr), 'sub_tree' => $column);
        }

        private function process_set_list($tokens) {
            $expr = array();
            $base_expr = "";

            foreach ($tokens as $token) {
                $trim = trim($token);

                if ($trim === ",") {
                    $expr[] = $this->getColumn($base_expr);
                    $base_expr = "";
                    continue;
                }

                $base_expr .= $token;
            }

            if (trim($base_expr) !== "") {
                $expr[] = $this->getColumn($base_expr);
            }

            return $expr;
        }

        /* This function processes the LIMIT section.
         start,end are set.  If only end is provided in the query
         then start is set to 0.
         */
        private function process_limit($tokens) {
            $start = "";
            $end = "";
            $comma = -1;

            for ($i = 0; $i < count($tokens); ++$i) {
                if (trim($tokens[$i]) === ",") {
                    $comma = $i;
                    break;
                }
            }

            for ($i = 0; $i < $comma; ++$i) {
                $start .= $tokens[$i];
            }

            for ($i = $comma + 1; $i < count($tokens); ++$i) {
                $end .= $tokens[$i];
            }

            return array('start' => trim($start), 'end' => trim($end));
        }

        /* This function processes the SELECT section.  It splits the clauses at the commas.
         Each clause is then processed by process_select_expr() and the results are added to
         the expression list.
        
         Finally, at the end, the epxression list is returned.
         */
        private function process_select(&$tokens) {
            $expression = "";
            $expr = array();
            foreach ($tokens as $token) {
                if (trim($token) === ',') {
                    $expr[] = $this->process_select_expr(trim($expression));
                    $expression = "";
                } else {
                    $expression .= $token;
                }
            }
            if ($expression) {
                $expr[] = $this->process_select_expr(trim($expression));
            }
            return $expr;
        }

        private function revokeEscaping($sql) {
            $sql = trim($sql);
            if (($sql[0] === '`') && ($sql[strlen($sql) - 1] === '`')) {
                $sql = substr($sql, 1, -1);
            }
            return str_replace('``', '`', $sql);
        }

        /* This fuction processes each SELECT clause.  We determine what (if any) alias
         is provided, and we set the type of expression.
         */
        private function process_select_expr($expression) {

            $tokens = $this->split_sql($expression);
            $token_count = count($tokens);

            /* Determine if there is an explicit alias after the AS clause.
             If AS is found, then the next non-whitespace token is captured as the alias.
             The tokens after (and including) the AS are removed.
             */
            $base_expr = "";
            $stripped = array();
            $capture = false;
            $alias = false;
            $processed = false;
            for ($i = 0; $i < $token_count; ++$i) {
                $token = strtoupper($tokens[$i]);
                if (trim($token) !== "") {
                    $stripped[] = $tokens[$i];
                }

                if ($token == 'AS') {
                    $alias = array('as' => true, "name" => "", "base_expr" => $tokens[$i]);
                    $tokens[$i] = "";
                    array_pop($stripped); // remove it from the expression
                    $capture = true;
                    continue;
                }

                if ($capture) {
                    if (trim($token) !== "") {
                        $alias['name'] .= $tokens[$i];
                        array_pop($stripped);
                    }
                    $alias['base_expr'] .= $tokens[$i];
                    $tokens[$i] = "";
                    continue;
                }
                $base_expr .= $tokens[$i];
            }

            $stripped = $this->process_expr_list($stripped);

            # we remove the last token, if it is a colref,
            # it can be an alias without an AS
            $last = array_pop($stripped);
            if (!$alias && $last['expr_type'] == 'colref') {

                # check the token before the colref
                $prev = array_pop($stripped);

                if (isset($prev)
                        && ($prev['expr_type'] == 'reserved' || $prev['expr_type'] == 'const'
                                || $prev['expr_type'] == 'function' || $prev['expr_type'] == 'expression'
                                || $prev['expr_type'] == 'subquery' || $prev['expr_type'] == 'colref')) {

                    $alias = array('as' => false, 'name' => trim($last['base_expr']),
                                   'base_expr' => trim($last['base_expr']));
                    #remove the last token
                    array_pop($tokens);
                    $base_expr = join("", $tokens);
                }
            }

            if (!$alias) {
                $base_expr = join("", $tokens);
            } else {
                /* remove escape from the alias */
                $alias['name'] = $this->revokeEscaping(trim($alias['name']));
                $alias['base_expr'] = trim($alias['base_expr']);
            }

            # this is always done with $stripped, how we do it twice?
            $processed = $this->process_expr_list($tokens);

            # if there is only one part, we copy the expr_type
            # in all other cases we use "expression" as global type
            $type = 'expression';
            if (count($processed) == 1) {
                if ($processed[0]['expr_type'] != 'subquery') {
                    $type = $processed[0]['expr_type'];
                    $base_expr = $processed[0]['base_expr'];
                    $processed = $processed[0]['sub_tree']; // it can be FALSE
                }
            }

            return array('expr_type' => $type, 'alias' => $alias, 'base_expr' => trim($base_expr),
                         'sub_tree' => $processed);
        }

        private function process_from(&$tokens) {

            $parseInfo = $this->initParseInfoForFrom();
            $expr = array();

            $skip_next = false;
            $i = 0;

            foreach ($tokens as $token) {
                $upper = strtoupper(trim($token));

                if ($skip_next && $token !== "") {
                    $parseInfo['token_count']++;
                    $skip_next = false;
                    continue;
                } else {
                    if ($skip_next) {
                        continue;
                    }
                }

                switch ($upper) {
                case 'OUTER':
                case 'LEFT':
                case 'RIGHT':
                case 'NATURAL':
                case 'CROSS':
                case ',':
                case 'JOIN':
                case 'INNER':
                    break;

                default:
                    $parseInfo['expression'] .= $token;
                    if ($parseInfo['ref_type'] !== false) { # all after ON / USING
                        $parseInfo['ref_expr'] .= $token;
                    }
                    break;
                }

                switch ($upper) {
                case 'AS':
                    $parseInfo['alias'] = array('as' => true, 'name' => "", 'base_expr' => $token);
                    $parseInfo['token_count']++;
                    $n = 1;
                    $str = "";
                    while ($str == "") {
                        $parseInfo['alias']['base_expr'] .= ($tokens[$i + $n] === "" ? " " : $tokens[$i + $n]);
                        $str = trim($tokens[$i + $n]);
                        ++$n;
                    }
                    $parseInfo['alias']['name'] = $str;
                    $parseInfo['alias']['base_expr'] = trim($parseInfo['alias']['base_expr']);
                    continue;

                case 'INDEX':
                    if ($token_category == 'CREATE') {
                        $token_category = $upper;
                        continue 2;
                    }

                    break;

                case 'USING':
                case 'ON':
                    $parseInfo['ref_type'] = $upper;
                    $parseInfo['ref_expr'] = "";

                case 'CROSS':
                case 'USE':
                case 'FORCE':
                case 'IGNORE':
                case 'INNER':
                case 'OUTER':
                    $parseInfo['token_count']++;
                    continue;
                    break;

                case 'FOR':
                    $parseInfo['token_count']++;
                    $skip_next = true;
                    continue;
                    break;

                case 'LEFT':
                case 'RIGHT':
                case 'STRAIGHT_JOIN':
                    $parseInfo['next_join_type'] = $upper;
                    break;

                case ',':
                    $parseInfo['next_join_type'] = 'CROSS';

                case 'JOIN':
                    if ($parseInfo['subquery']) {
                        $parseInfo['sub_tree'] = $this->parse($this->removeParenthesisFromStart($parseInfo['subquery']));
                        $parseInfo['expression'] = $parseInfo['subquery'];
                    }

                    $expr[] = $this->processFromExpression($parseInfo);
                    $parseInfo = $this->initParseInfoForFrom($parseInfo);
                    break;

                default:
                    if ($upper === "") {
                        continue; # ends the switch statement!
                    }

                    if ($parseInfo['token_count'] === 0) {
                        if ($parseInfo['table'] === "") {
                            $parseInfo['table'] = $token;
                        }
                    } else if ($parseInfo['token_count'] === 1) {
                        $parseInfo['alias'] = array('as' => false, 'name' => trim($token), 'base_expr' => trim($token));
                    }
                    $parseInfo['token_count']++;
                    break;
                }
                ++$i;
            }

            $expr[] = $this->processFromExpression($parseInfo);
            return $expr;
        }

        private function initParseInfoForFrom($parseInfo = false) {
            # first init
            if ($parseInfo === false) {
                $parseInfo = array('join_type' => "", 'saved_join_type' => "JOIN");
            }
            # loop init
            return array('expression' => "", 'token_count' => 0, 'table' => "", 'alias' => false, 'join_type' => "",
                         'next_join_type' => "", 'saved_join_type' => $parseInfo['saved_join_type'],
                         'ref_type' => false, 'ref_expr' => false, 'base_expr' => false, 'sub_tree' => false,
                         'subquery' => "");
        }

        private function processFromExpression(&$parseInfo) {

            $res = array();

            # exchange the join types (join_type is save now, saved_join_type holds the next one)
            $parseInfo['join_type'] = $parseInfo['saved_join_type']; # initialized with JOIN
            $parseInfo['saved_join_type'] = ($parseInfo['next_join_type'] ? $parseInfo['next_join_type'] : 'JOIN');

            # we have a reg_expr, so we have to parse it
            if ($parseInfo['ref_expr'] !== false) {
                $unparsed = $this->split_sql($this->removeParenthesisFromStart($parseInfo['ref_expr']));

                // here we can get a comma separated list
                foreach ($unparsed as $k => $v) {
                    if (trim($v) === ',') {
                        $unparsed[$k] = "";
                    }
                }
                $parseInfo['ref_expr'] = $this->process_expr_list($unparsed);
            }

            # there is an expression, we have to parse it
            if (substr(trim($parseInfo['table']), 0, 1) == '(') {
                $parseInfo['expression'] = $this->removeParenthesisFromStart($parseInfo['table']);

                if (preg_match("/^\\s*select/i", $parseInfo['expression'])) {
                    $parseInfo['sub_tree'] = $this->parse($parseInfo['expression']);
                    $res['expr_type'] = 'subquery';
                } else {
                    $tmp = $this->split_sql($parseInfo['expression']);
                    $parseInfo['sub_tree'] = $this->process_from($tmp);
                    $res['expr_type'] = 'table_expression';
                }
            } else {
                $res['expr_type'] = 'table';
                $res['table'] = $parseInfo['table'];
            }

            $res['alias'] = $parseInfo['alias'];
            $res['join_type'] = $parseInfo['join_type'];
            $res['ref_type'] = $parseInfo['ref_type'];
            $res['ref_clause'] = $parseInfo['ref_expr'];
            $res['base_expr'] = trim($parseInfo['expression']);
            $res['sub_tree'] = $parseInfo['sub_tree'];
            return $res;
        }

        private function processOrderExpression(&$parseInfo, $select) {
            $parseInfo['expr'] = trim($parseInfo['expr']);

            if ($parseInfo['expr'] === "") {
                return false;
            }

            $parseInfo['expr'] = trim($this->revokeEscaping($parseInfo['expr']));

            if (is_numeric($parseInfo['expr'])) {
                $parseInfo['type'] = 'pos';
            } else {
                #search to see if the expression matches an alias
                foreach ($select as $clause) {
                    if (!$clause['alias']) {
                        continue;
                    }
                    if ($clause['alias']['name'] === $parseInfo['expr']) {
                        $parseInfo['type'] = 'alias';
                    }
                }

                if (!$parseInfo['type']) {
                    $parseInfo['type'] = "expression";
                }
            }

            return array('type' => $parseInfo['type'], 'base_expr' => $parseInfo['expr'],
                         'direction' => $parseInfo['dir']);
        }

        private function initParseInfoForOrder() {
            return array('expr' => "", 'dir' => "ASC", 'type' => 'expression');
        }

        private function process_order($tokens, $select) {
            $out = array();
            $parseInfo = $this->initParseInfoForOrder();

            if (!$tokens) {
                return false;
            }

            foreach ($tokens as $token) {
                $upper = strtoupper(trim($token));
                switch ($upper) {
                case ',':
                    $out[] = $this->processOrderExpression($parseInfo, $select);
                    $parseInfo = $this->initParseInfoForOrder();
                    break;

                case 'DESC':
                    $parseInfo['dir'] = "DESC";
                    break;

                case 'ASC':
                    $parseInfo['dir'] = "ASC";
                    break;

                default:
                    $parseInfo['expr'] .= $token;

                }
            }

            $out[] = $this->processOrderExpression($parseInfo, $select);
            return $out;
        }

        private function process_group(&$tokens, &$select) {
            $out = array();
            $parseInfo = $this->initParseInfoForOrder();

            if (!$tokens) {
                return false;
            }

            foreach ($tokens as $token) {
                $trim = strtoupper(trim($token));
                switch ($trim) {
                case ',':
                    $parsed = $this->processOrderExpression($parseInfo, $select);
                    unset($parsed['direction']);

                    $out[] = $parsed;
                    $parseInfo = $this->initParseInfoForOrder();
                    break;
                default:
                    $parseInfo['expr'] .= $token;

                }
            }

            $parsed = $this->processOrderExpression($parseInfo, $select);
            unset($parsed['direction']);
            $out[] = $parsed;

            return $out;
        }

        /**
         *  Some sections are just lists of expressions, like the WHERE and HAVING clauses.
         *  This function processes these sections.  
         *  Recursive.
         */
        private function process_expr_list($tokens) {

            $parseInfo = new PHPSQLParserInfo();
            $skip_next = false;

            foreach ($tokens as $k => $v) {

                $parseInfo->setToken($k, $v);

                if ($parseInfo->isEmptyToken()) {
                    continue;
                }

                if ($skip_next) {
                    # skip the next non-whitespace token
                    $skip_next = false;
                    continue;
                }

                /* is it a subquery?*/
                if ($parseInfo->isSubQuery()) {
                    $parseInfo->processAsSubQuery();

                } elseif ($parseInfo->isSurroundedByParenthesis()) {

                    # if we have a colref followed by a parenthesis pair,
                    # it isn't a colref, it is a user-function
                    if ($parseInfo->isPreviousTokenTypeEquals(PHPSQLParserConstants::COLREF)) {
                        $parseInfo->processAsColRef();
                    }

                    if ($parseInfo->isPreviousTokenTypeEquals(PHPSQLParserConstants::FUNC)) {
                        $parseInfo->processAsFunction();
                    }

                    if ($parseInfo->isPreviousTokenTypeEquals(PHPSQLParserConstants::AGG_FUNC)) {
                        $parseInfo->processAsAggregateFunction();
                    }

                    if ($parseInfo->isPreviousTokenEquals("IN")) {
                        $parseInfo->processAsInList();
                    }

                    if ($parseInfo->isPreviousTokenEquals("AGAINST")) {
                        $parseInfo->processAsAgainstArguments();
                    }

                } else {
                    /* it is either an operator, a colref or a constant */
                    switch ($parseInfo->upper) {

                    case '*':
                        if ($parseInfo->processAsAsterisk()) {
                            continue 2;
                        }
                        break;

                    case 'AND':
                    case '&&':
                    case 'BETWEEN':
                    case 'AND':
                    case 'BINARY':
                    case '&':
                    case '~':
                    case '|':
                    case '^':
                    case 'DIV':
                    case '/':
                    case '<=>':
                    case '=':
                    case '>=':
                    case '>':
                    case 'IS':
                    case 'NOT':
                    case 'NULL':
                    case '<<':
                    case '<=':
                    case '<':
                    case 'LIKE':
                    case '%':
                    case '!=':
                    case '<>':
                    case 'REGEXP':
                    case '!':
                    case '||':
                    case 'OR':
                    case '>>':
                    case 'RLIKE':
                    case 'SOUNDS':
                    case 'XOR':
                    case 'IN':
                        $parseInfo->processAsOperator();
                        break;

                    case '-':
                    case '+':
                        $parseInfo->processAsSign();
                        break;

                    default:
                        $parseInfo->processAsUnknown();
                    }
                }

                /* is a reserved word? */
                if (!$parseInfo->isTokenTypeWithin(
                        array(PHPSQLParserConstants::OPERATOR, PHPSQLParserConstants::INLIST,
                                PHPSQLParserConstants::FUNC, PHPSQLParserConstants::AGG_FUNC))
                        && $parseInfo->isReservedWord()) {

                    if ($parseInfo->isKnownFunction()) {
                        $parseInfo->processAsKnownFunction();
                    } else {
                        $parseInfo->processAsReservedWord();

                    }
                }

                # when will occur this?
                if (!$parseInfo->tokenType) {
                    echo ("********** tokenType not set!");
                    if ($parseInfo->upper[0] === "(") {
                        $local_expr = $this->removeParenthesisFromStart($parseInfo->trim);
                    } else {
                        $local_expr = $parseInfo->trim;
                    }
                    $parseInfo->processed = $this->process_expr_list($this->split_sql($local_expr));
                    $parseInfo->tokenType = PHPSQLParserConstants::EXPRESSION;

                    if (count($parseInfo->processed) === 1) {
                        $parseInfo->tokenType = $parseInfo->processed[0]['expr_type'];
                        $parseInfo->base_expr = $parseInfo->processed[0]['base_expr'];
                        $parseInfo->processed = $parseInfo->processed[0]['sub_tree'];
                    }

                }

                $parseInfo = new PHPSQLParserInfo($parseInfo);
            } // end of for-loop

            return (is_array($parseInfo->expr) ? $parseInfo->expr : false);
        }

        private function process_update($tokens) {

        }

        private function process_delete($tokens) {
            $tables = array();
            $del = $tokens['DELETE'];

            foreach ($tokens['DELETE'] as $expression) {
                if ($expression != 'DELETE' && trim($expression, ' .*') != "" && $expression != ',') {
                    $tables[] = trim($expression, '.* ');
                }
            }

            if (empty($tables)) {
                foreach ($tokens['FROM'] as $table) {
                    $tables[] = $table['table'];
                }
            }

            $tokens['DELETE'] = array('TABLES' => $tables);
            return $tokens;
        }

        private function process_insert($tokens, $token_category = 'INSERT') {
            $table = "";
            $cols = array();

            $into = $tokens['INTO'];
            foreach ($into as $token) {
                if (trim($token) === "")
                    continue;
                if ($table === "") {
                    $table = $token;
                } elseif (empty($cols)) {
                    $cols[] = $token;
                }
            }

            if (empty($cols)) {
                $cols = false;
            } else {
                $columns = explode(",", $this->removeParenthesisFromStart($cols[0]));
                $cols = array();
                foreach ($columns as $k => $v) {
                    $cols[] = array('expr_type' => 'colref', 'base_expr' => trim($v));
                }
            }

            unset($tokens['INTO']);
            $tokens[$token_category] = array('table' => $table, 'columns' => $cols, 'base_expr' => $table);
            return $tokens;
        }

        private function process_record($unparsed) {

            $unparsed = $this->removeParenthesisFromStart($unparsed);
            $values = $this->split_sql($unparsed);

            foreach ($values as $k => $v) {
                if (trim($v) === ",") {
                    $values[$k] = "";
                }
            }
            return $this->process_expr_list($values);
        }

        private function process_values($tokens) {

            $unparsed = "";
            foreach ($tokens['VALUES'] as $k => $v) {
                if (trim($v) === "") {
                    continue;
                }
                $unparsed .= $v;
            }

            $values = $this->split_sql($unparsed);

            $parsed = array();
            foreach ($values as $k => $v) {
                if (trim($v) === ",") {
                    unset($values[$k]);
                } else {
                    $values[$k] = array('expr_type' => 'record', 'base_expr' => $v, 'data' => $this->process_record($v));
                }
            }

            $tokens['VALUES'] = array_values($values);
            return $tokens;
        }

        /**
         * TODO: This is a dummy function, we cannot parse INTO as part of SELECT
         * at the moment
         */
        private function process_into($tokens) {
            $unparsed = $tokens['INTO'];
            foreach ($unparsed as $k => $token) {
                if ((trim($token) === "") || (trim($token) === ",")) {
                    unset($unparsed[$k]);
                }
            }
            $tokens['INTO'] = array_values($unparsed);
            return $tokens;
        }
    }

    /**
     * 
     * This class calculates the positions 
     * of base_expr within the origina SQL statement.
     * 
     * @author arothe
     * 
     */
    class PositionCalculator extends PHPSQLParserConstants {

        private $allowedOnOperator;
        private $allowedOnOther;

        public function __construct() {
            parent::__construct();
            $this->allowedOnOperator = $this->getAllowedOnOperator();
            $this->allowedOnOther = $this->getAllowedOnOther();
        }

        private function getAllowedOnOperator() {
            return array("\t", "\n", "\r", " ", ",", "(", ")", "_", "'");
        }

        private function getAllowedOnOther() {
            return array("\t", "\n", "\r", " ", ",", "(", ")", "<", ">", "*", "+", "-", "/", "|", "&", "=", "!", ";");
        }

        private function printPos($text, $sql, $charPos, $key, $parsed, $backtracking) {
            if (!isset($_ENV['DEBUG'])) {
                return;
            }

            $spaces = "";
            $caller = debug_backtrace();
            $i = 1;
            while ($caller[$i]['function'] === 'lookForBaseExpression') {
                $spaces .= "   ";
                $i++;
            }
            $holdem = substr($sql, 0, $charPos) . "^" . substr($sql, $charPos);
            echo $spaces . $text . " key:" . $key . "  parsed:" . $parsed . " back:" . serialize($backtracking) . " "
                    . $holdem . "\n";
        }

        public function setPositionsWithinSQL($sql, $parsed) {
            $charPos = 0;
            $backtracking = array();
            $this->lookForBaseExpression($sql, $charPos, $parsed, 0, $backtracking);
            return $parsed;
        }

        private function findPositionWithinString($sql, $value, $expr_type) {

            $offset = 0;
            $ok = false;
            while (true) {

                $pos = strpos($sql, $value, $offset);
                if ($pos === false) {
                    break;
                }

                $before = "";
                if ($pos > 0) {
                    $before = $sql[$pos - 1];
                }

                $after = "";
                if (isset($sql[$pos + strlen($value)])) {
                    $after = $sql[$pos + strlen($value)];
                }

                # if we have an operator, it should be surrounded by
                # whitespace, comma, parenthesis, digit or letter, end_of_string
                # an operator should not be surrounded by another operator

                if ($expr_type === 'operator') {

                    $ok = ($before === "" || in_array($before, $this->allowedOnOperator, true))
                            || (strtolower($before) >= 'a' && strtolower($before) <= 'z')
                            || ($before >= '0' && $before <= '9');
                    $ok = $ok
                            && ($after === "" || in_array($after, $this->allowedOnOperator, true)
                                    || (strtolower($after) >= 'a' && strtolower($after) <= 'z')
                                    || ($after >= '0' && $after <= '9'));

                    if (!$ok) {
                        $offset = $pos + 1;
                        continue;
                    }

                    break;
                }

                # in all other cases we accept
                # whitespace, comma, operators, parenthesis and end_of_string

                $ok = ($before === "" || in_array($before, $this->allowedOnOther, true));
                $ok = $ok && ($after === "" || in_array($after, $this->allowedOnOther, true));

                if ($ok) {
                    break;
                }

                $offset = $pos + 1;
            }

            return $pos;
        }

        private function lookForBaseExpression($sql, &$charPos, &$parsed, $key, &$backtracking) {
            if (!is_numeric($key)) {
                if (in_array($key, array('UNION', 'UNION ALL'), true)
                        || ($key === 'expr_type' && $parsed === 'expression')
                        || ($key === 'expr_type' && $parsed === 'subquery')
                        || ($key === 'expr_type' && $parsed === 'table_expression')
                        || ($key === 'expr_type' && $parsed === 'record')
                        || ($key === 'expr_type' && $parsed === 'in-list') || ($key === 'alias' && $parsed !== false)) {
                    # we hold the current position and come back after the next base_expr
                    # we do this, because the next base_expr contains the complete expression/subquery/record
                    # and we have to look into it too
                    $backtracking[] = $charPos;

                } elseif (($key === 'ref_clause' || $key === 'columns') && $parsed !== false) {
                    # we hold the current position and come back after n base_expr(s)
                    # there is an array of sub-elements before (!) the base_expr clause of the current element
                    # so we go through the sub-elements and must come at the end
                    $backtracking[] = $charPos;
                    for ($i = 1; $i < count($parsed); $i++) {
                        $backtracking[] = false; # backtracking only after n base_expr!
                    }
                } elseif ($key === 'sub_tree' && $parsed !== false) {
                    # we prevent wrong backtracking on subtrees (too much array_pop())
                    # there is an array of sub-elements after(!) the base_expr clause of the current element
                    # so we go through the sub-elements and must not come back at the end
                    for ($i = 1; $i < count($parsed); $i++) {
                        $backtracking[] = false;
                    }
                } else {
                    # move the current pos after the keyword
                    # SELECT, WHERE, INSERT etc.
                    if (in_array($key, $this->reserved)) {
                        $charPos = stripos($sql, $key, $charPos);
                        $charPos += strlen($key);
                    }
                }
            }

            if (!is_array($parsed)) {
                return;
            }

            foreach ($parsed as $key => $value) {
                if ($key === 'base_expr') {

                    #$this->printPos("0", $sql, $charPos, $key, $value, $backtracking);

                    $subject = substr($sql, $charPos);
                    $pos = $this->findPositionWithinString($subject, $value,
                            isset($parsed['expr_type']) ? $parsed['expr_type'] : 'alias');
                    if ($pos === false) {
                        throw new UnableToCalculatePositionException($value, $subject);
                    }

                    $parsed['position'] = $charPos + $pos;
                    $charPos += $pos + strlen($value);

                    #$this->printPos("1", $sql, $charPos, $key, $value, $backtracking);

                    $oldPos = array_pop($backtracking);
                    if (isset($oldPos) && $oldPos !== false) {
                        $charPos = $oldPos;
                    }

                    #$this->printPos("2", $sql, $charPos, $key, $value, $backtracking);

                } else {
                    $this->lookForBaseExpression($sql, $charPos, $parsed[$key], $key, $backtracking);
                }
            }
        }
    }

    class UnableToCalculatePositionException extends Exception {

        protected $needle;
        protected $haystack;

        public function __construct($needle, $haystack) {
            $this->needle = $needle;
            $this->haystack = $haystack;
            parent::__construct("cannot calculate position of " . $needle . " within " . $haystack, 5);
        }

        public function getNeedle() {
            return $this->needle;
        }

        public function getHaystack() {
            return $this->haystack;
        }
    }

    class InvalidParameterException extends InvalidArgumentException {

        protected $argument;

        public function __construct($argument) {
            $this->argument = $argument;
            parent::__construct("no SQL string to parse: \n" . $argument, 10);
        }

        public function getArgument() {
            return $this->argument;
        }
    }

    define('HAVE_PHP_SQL_PARSER', 1);
}

