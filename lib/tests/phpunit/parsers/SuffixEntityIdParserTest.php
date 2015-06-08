<?php

namespace Wikibase\Lib\Test;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Parsers\SuffixEntityIdParser;

/**
 * @covers Wikibase\Lib\Parsers\SuffixEntityIdParser
 *
 * @group ValueParsers
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SuffixEntityIdParserTest extends \PHPUnit_Framework_TestCase {

	public function provideParse() {
		return array(
			'base URI' => array( 'http://acme.test/entity/', 'http://acme.test/entity/Q14', new ItemId( 'Q14' ) ),
			'interwiki prefix' => array( 'wikidata:', 'wikidata:P14', new PropertyId( 'P14' ) ),
		);
	}

	/**
	 * @dataProvider provideParse
	 */
	public function testParse( $regexp, $input, $expected ) {
		$parser = new SuffixEntityIdParser( $regexp, new BasicEntityIdParser() );
		$this->assertEquals( $expected, $parser->parse( $input ) );
	}

	public function provideParse_invalid() {
		return array(
			'mismatching prefix' => array( 'http://acme.test/entity/', 'http://acme.test/Q14' ),
			'bad ID after prefix' => array( 'http://acme.test/entity/', 'http://acme.test/entity/XYYZ' ),
		);
	}

	/**
	 * @dataProvider provideParse_invalid
	 */
	public function testParse_invalid( $regexp, $input ) {
		$parser = new SuffixEntityIdParser( $regexp, new BasicEntityIdParser() );

		$this->setExpectedException( 'Wikibase\DataModel\Entity\EntityIdParsingException' );
		$parser->parse( $input );
	}

}
