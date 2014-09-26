<?php
/**
 * issue150Test.php
 *
 * Test case for PHPSQLParser.
 *
 * PHP version 5
 *
 * LICENSE:
 * Copyright (c) 2010-2014 Justin Swanhart and André Rothe
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 * @author    André Rothe <andre.rothe@phosco.info>
 * @copyright 2010-2014 Justin Swanhart and André Rothe
 * @license   http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @version   SVN: $Id$
 * 
 */

namespace PHPSQLParser\Test\Parser;
require_once dirname(__FILE__) . '/../../../vendor/autoload.php';

use PHPSQLParser\PHPSQLParser;
use Analog\Analog;

class Issue150Test extends \PHPUnit_Framework_TestCase {
	
    public function testIssue150() {
		$sql = "SELECT cast((ra/cos(cast(dec*30 as int)/30.0))*30 as int)/30.0 as raCosDec,
cast(dec*30 as int)/30.0 as dec,
count(*) as pop
FROM Galaxy as G,
fHTM_Cover('CONVEX J2000 6 175 -5 175 5 185 5 185 -5') as T
WHERE htmID between T.HTMIDstart* power(2,28)and T. HTMIDend*power(2,28)
and ra between 175 and 185
and dec between -5 and 5
and u-g > 1
and r < 21.5
GROUP BY cast((ra/cos(cast(dec*30 as int)/30.0))*30 as int)/30.0,
cast(dec*30 as int)/30.0 ";
		$parser = new PHPSQLParser($sql, false);
		$p = $parser->parsed;
		Analog::log(print_r($p, true));
		$calc = $p['SELECT'][0]['sub_tree'][0]['sub_tree'][0]['sub_tree'][0]['sub_tree'][2]['sub_tree'][0]['sub_tree'][0]['sub_tree'][0]['sub_tree'][0]['expr_type'];
		$this->assertEquals('colref', $calc, 'wrong reserved keyword');
    }
}

//$a = new Issue150Test();
//$a->testIssue150();

?>
