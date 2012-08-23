<?php
/**
 * lexer.php
 *
 * This file contains the lexer, which splits the SQL statement just before parsing.
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

require_once(dirname(__FILE__) . '/parser-utils.php');
require_once(dirname(__FILE__) . '/lexer-splitter.php');
require_once(dirname(__FILE__) . '/exceptions.php');
require_once(dirname(__FILE__) . '/tokens/token.php');
require_once(dirname(__FILE__) . '/tokens/comment.php');
require_once(dirname(__FILE__) . '/tokens/string-literal.php');
require_once(dirname(__FILE__) . '/tokens/hex-string-literal.php');
require_once(dirname(__FILE__) . '/tokens/binary-string-literal.php');

/**
 * This class splits the SQL string into little parts, which the parser can
 * use to build the result array.
 * 
 * @author arothe
 *
 */
class PHPSQLLexer extends PHPSQLParserUtils {

    private $splitters;

    public function __construct() {
        $this->splitters = new LexerSplitter();
    }

    public function split($sql) {
        if (!is_string($sql)) {
            throw new InvalidParameterException($sql);
        }

        $tokenList = array();
        $token = "";

        $splitLen = $this->splitters->getMaxLengthOfSplitter();
        $found = false;
        $len = strlen($sql);
        $pos = 0;

        while ($pos < $len) {

            for ($i = $splitLen; $i > 0; $i--) {
                $substr = substr($sql, $pos, $i);
                if ($this->splitters->isSplitter($substr)) {

                    if ($token !== "") {
                        $tokenList[] = new Token($token);
                    }

                    $tokenList[] = new Token($substr);
                    $pos += $i;
                    $token = "";

                    continue 2;
                }
            }

            $token .= $sql[$pos];
            $pos++;
        }

        if ($token !== "") {
            $tokenList[] = new Token($token);
        }

        $tokenList = $this->concatEscapeSequences($tokenList);
        $tokenList = $this->balanceBackticks($tokenList);
        $tokenList = $this->concatColReferences($tokenList);
        $tokenList = $this->balanceParenthesis($tokenList);
        $tokenList = $this->balanceMultilineComments($tokenList);
        $tokenList = $this->concatInlineComments($tokenList);
        $tokenList = $this->concatUserDefinedVariables($tokenList);
        return $tokenList;
    }

    private function concatUserDefinedVariables($tokenList) {
        $i = 0;
        $cnt = count($tokenList);
        $userdef = false;

        while ($i < $cnt) {

            if (!isset($tokenList[$i])) {
                $i++;
                continue;
            }

            $token = $tokenList[$i];

            if ($userdef !== false) {
                $tokenList[$userdef]->add($token);
                unset($tokenList[$i]);
                if ($token->get() !== "@") {
                    $userdef = false;
                }
            }

            if ($userdef === false && $token->get() === "@") {
                $userdef = $i;
                $tokenList[$userdef] = new Variable($token->get());
            }

            $i++;
        }

        return array_values($tokenList);
    }

    private function concatInlineComments($tokenList) {

        $i = 0;
        $cnt = count($tokenList);
        $comment = false;

        while ($i < $cnt) {

            if (!isset($tokenList[$i])) {
                $i++;
                continue;
            }

            $token = $tokenList[$i];

            if ($comment !== false) {
                if ($token->isEOL()) {
                    $comment = false;
                } else {
                    unset($tokenList[$i]);
                    $tokenList[$comment]->add($token);
                }
            }

            if (($comment === false) && ($token->get() === "-")) {
                if (isset($tokenList[$i + 1]) && $tokenList[$i + 1]->get() === "-") {
                    $comment = $i;
                    $tokenList[$i] = new Comment("--");
                    $i++;
                    unset($tokenList[$i]);
                    continue;
                }
            }

            $i++;
        }

        return array_values($tokenList);
    }

    private function balanceMultilineComments($tokenList) {

        $i = 0;
        $cnt = count($tokenList);
        $comment = false;

        while ($i < $cnt) {

            if (!isset($tokenList[$i])) {
                $i++;
                continue;
            }

            $token = $tokenList[$i];

            if ($comment !== false) {
                unset($tokenList[$i]);
                $tokenList[$comment]->add($token);
                if ($token->get() === "*" && isset($tokenList[$i + 1]) && $tokenList[$i + 1]->get() === "/") {
                    $tokenList[$comment]->add($tokenList[$i + 1]);
                    unset($tokenList[$i + 1]);
                    $comment = false;
                }
            }

            if (($comment === false) && ($token->get() === "/")) {
                if (isset($tokenList[$i + 1]) && $tokenList[$i + 1]->get() === "*") {
                    $comment = $i;
                    $tokenList[$i] = new Comment("/*");
                    $i++;
                    unset($tokenList[$i]);
                    continue;
                }
            }

            $i++;
        }
        return array_values($tokenList);
    }

    // TODO: see http://dev.mysql.com/doc/refman/5.1/de/string-syntax.html
    // there are a lot of possibilities how backticks have beed combined
    private function balanceBackticks($tokenList) {
        $i = 0;
        $cnt = count($tokenList);
        while ($i < $cnt) {

            if (!isset($tokenList[$i])) {
                $i++;
                continue;
            }

            $token = $tokenList[$i];

            if ($token->isBacktick()) {
                $tokenList = $this->balanceCharacter($tokenList, $i, $token);
            }

            $i++;
        }

        return $tokenList;
    }

    # backticks are not balanced within one token, so we have
    # to re-combine some tokens
    private function balanceCharacter($tokenList, $idx, $char) {

        $token_count = count($tokenList);
        $i = $idx + 1;
        while ($i < $token_count) {

            if (!isset($tokenList[$i])) {
                $i++;
                continue;
            }

            $token = $tokenList[$i];
            $tokenList[$idx]->add($token);
            unset($tokenList[$i]);

            if ($token->get() === $char) {
                break;
            }

            $i++;
        }
        return array_values($tokenList);
    }

    /*
     * does the token ends with dot?
     * concat it with the next token
     * 
     * does the token starts with a dot?
     * concat it with the previous token
     * 
     * TODO: check decimal value constants like 1.02
     */
    private function concatColReferences($tokenList) {

        $cnt = count($tokenList);
        $i = 0;
        while ($i < $cnt) {

            if (!isset($tokenList[$i])) {
                $i++;
                continue;
            }

            if ($tokenList[$i]->startsWith(".")) {

                // concat the previous tokens, till the token has been changed
                $k = $i - 1;
                $len = strlen($tokenList[$i]->get());
                while (($k >= 0) && ($len === strlen($tokenList[$i]->get()))) {
                    if (!isset($tokenList[$k])) { # FIXME: this can be wrong if we have schema . table . column
                        $k--;
                        continue;
                    }
                    $tokenList[$k]->add($tokenList[$i]);
                    $tokenList[$i] = $tokenList[$k];
                    unset($tokenList[$k]);
                    $k--;
                }
            }

            if ($tokenList[$i]->endsWith('.')) {

                // concat the next tokens, till the token has been changed
                $k = $i + 1;
                $len = strlen($tokenList[$i]->get());
                while (($k < $cnt) && ($len === strlen($tokenList[$i]->get()))) {
                    if (!isset($tokenList[$k])) {
                        $k++;
                        continue;
                    }
                    $tokenList[$i]->add($tokenList[$k]);
                    unset($tokenList[$k]);
                    $k++;
                }
            }

            $i++;
        }

        return array_values($tokenList);
    }

    private function concatEscapeSequences($tokenList) {
        $tokenCount = count($tokenList);
        $i = 0;
        while ($i < $tokenCount) {

            $token = $tokenList[$i];

            if ($token->endsWith("\\")) {
                $i++;
                if (isset($tokenList[$i])) {
                    $token->add($tokenList[$i]);
                    unset($tokenList[$i]);
                }
            }
            $i++;
        }
        return array_values($tokenList);
    }

    private function balanceParenthesis($tokenList) {
        $tokenCount = count($tokenList);
        $i = 0;
        while ($i < $tokenCount) {
            if ($tokenList[$i]->get() !== '(') {
                $i++;
                continue;
            }
            $count = 1;
            for ($n = $i + 1; $n < $tokenCount; $n++) {
                $token = $tokenList[$n];
                if ($token->get() === '(') {
                    $count++;
                }
                if ($token->get() === ')') {
                    $count--;
                }
                $tokenList[$i]->add($token);
                unset($tokenList[$n]);
                if ($count === 0) {
                    $n++;
                    break;
                }
            }
            $i = $n;
        }
        return array_values($tokenList);
    }
}
