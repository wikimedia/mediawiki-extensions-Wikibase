<?php

namespace Wikibase\Test;

use Wikibase\Repo\LinkedData\HttpAcceptParser;

/**
 * @covers Wikibase\Repo\LinkedData\HttpAcceptParser
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class HttpAcceptParserTest extends \PHPUnit_Framework_TestCase {

	public function provideParseWeights() {
		return array(
			array( // #0
				'',
				[]
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
