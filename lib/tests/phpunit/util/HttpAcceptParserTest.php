<?php

namespace Wikibase\Test;
use Wikibase\HttpAcceptNegotiator;
use Wikibase\HttpAcceptParser;
use Wikibase\TermIndex;
use Wikibase\ItemContent;
use Wikibase\Item;
use Wikibase\Term;

/**
 * Test for HttpAcceptParser
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 * @covers HttpAcceptNegotiator
 */
class HttpAcceptParserTest extends \PHPUnit_Framework_TestCase {

	public function provideParseWeights() {
		return array(
			array( // #0
				'',
				array()
			),
			array( // #1
				'Foo/Bar',
				array( 'foo/bar' => 1 )
			),
			array( // #2
				'Accept: text/plain',
				array( 'text/plain' => 1 )
			),
			array( // #3
				'Accept: application/vnd.php.serialized, application/rdf+xml',
				array( 'application/vnd.php.serialized' => 1, 'application/rdf+xml' => 1 )
			),
			array( // #4
				'foo; q=0.2, xoo; q=0,text/n3',
				array( 'text/n3' => 1, 'foo' => 0.2 )
			),
			array( // #5
				'*; q=0.2, */*; q=0.1,text/*',
				array( 'text/*' => 1, '*' => 0.2, '*/*' => 0.1 )
			),
			// TODO: nicely ignore additional type paramerters
			//array( // #6
			//	'Foo; q=0.2, Xoo; level=3, Bar; charset=xyz; q=0.4',
			//	array( 'xoo' => 1, 'bar' => 0.4, 'foo' => 0.1 )
			//),
		);
	}

	/**
	 * @dataProvider provideParseWeights
	 */
	public function testParseWeights( $header, $expected ) {
		$parser = new HttpAcceptParser();
		$actual = $parser->parseWeights( $header );

		$this->assertEquals( $expected, $actual ); // shouldn't be sensitive to order
	}

}
