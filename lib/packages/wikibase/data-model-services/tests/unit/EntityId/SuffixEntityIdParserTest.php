<?php

namespace Wikibase\DataModel\Services\Tests\EntityId;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\SuffixEntityIdParser;

/**
 * @covers Wikibase\DataModel\Services\EntityId\SuffixEntityIdParser
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class SuffixEntityIdParserTest extends \PHPUnit_Framework_TestCase {

	public function validInputProvider() {
		return [
			'base URI' => [ 'http://acme.test/entity/', 'http://acme.test/entity/Q14', new ItemId( 'Q14' ) ],
			'interwiki prefix' => [ 'wikidata:', 'wikidata:P14', new PropertyId( 'P14' ) ],
		];
	}

	/**
	 * @dataProvider validInputProvider
	 */
	public function testParse( $prefix, $input, $expected ) {
		$parser = new SuffixEntityIdParser( $prefix, new BasicEntityIdParser() );
		$this->assertEquals( $expected, $parser->parse( $input ) );
	}

	public function invalidInputProvider() {
		return [
			'mismatching prefix' => [ 'http://acme.test/entity/', 'http://www.wikidata.org/entity/Q14' ],
			'incomplete prefix' => [ 'http://acme.test/entity/', 'http://acme.test/Q14' ],
			'bad ID after prefix' => [ 'http://acme.test/entity/', 'http://acme.test/entity/XYYZ' ],
			'extra stuff after ID' => [ 'http://acme.test/entity/', 'http://acme.test/entity/Q14#foo' ],
			'input is shorter than prefix' => [ 'http://acme.test/entity/', 'http://acme.test/' ],
			'input is same as prefix' => [ 'http://acme.test/entity/', 'http://acme.test/entity/' ],
			'input is lexicographically smaller than prefix' => [ 'http://acme.test/entity/', 'http://aaaa.test/entity/Q14' ],
			'input is lexicographically greater than prefix' => [ 'http://acme.test/entity/', 'http://cccc.test/entity/Q14' ],
		];
	}

	/**
	 * @dataProvider invalidInputProvider
	 */
	public function testParse_invalid( $prefix, $input ) {
		$parser = new SuffixEntityIdParser( $prefix, new BasicEntityIdParser() );

		$this->setExpectedException( 'Wikibase\DataModel\Entity\EntityIdParsingException' );
		$parser->parse( $input );
	}

}
