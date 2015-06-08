<?php

namespace Wikibase\Lib\Test;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Parsers\ExtractingEntityIdParser;

/**
 * @covers Wikibase\Lib\Parsers\ExtractingEntityIdParser
 *
 * @group ValueParsers
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ExtractingEntityIdParserTest extends \PHPUnit_Framework_TestCase {

	public function provideParse() {
		return array(
			array( '!^https?://acme.test/entity/(.*)$!', 'http://acme.test/entity/Q14', new ItemId( 'Q14' ) ),
			array( '!^(?:https?)://acme.test/entity/(.*?)(#.*)?$!', 'http://acme.test/entity/P14', new PropertyId( 'P14' ) ),
		);
	}

	/**
	 * @dataProvider provideParse
	 */
	public function testParse( $regexp, $input, $expected ) {
		$parser = new ExtractingEntityIdParser( $regexp, new BasicEntityIdParser() );
		$this->assertEquals( new ItemId( 'Q14' ), $parser->parse( 'http://acme.test/entity/Q14' ) );
	}

	public function provideParse_invalid() {
		return array(
			array( '!^https?://acme.test/entity/(.*)$!', 'http://acme.test/Q14' ),
			array( '!^https?://acme.test/entity/(.*)$!', 'http://acme.test/entity/XYYZ' ),
			array( '!^https?://acme.test/entity/(.*)$!', 'http://acme.test/Q14#x' ),
			array( '!^https?://acme.test/entity/.*$!', 'http://acme.test/entity/Q14' ),
		);
	}

	/**
	 * @dataProvider provideParse_invalid
	 */
	public function testParse_invalid( $regexp, $input ) {
		$parser = new ExtractingEntityIdParser( $regexp, new BasicEntityIdParser() );

		$this->setExpectedException( 'Wikibase\DataModel\Entity\EntityIdParsingException' );
		$parser->parse( $input );
	}

	public function testNewFromBaseUri() {
		$parser = ExtractingEntityIdParser::newFromBaseUri( 'http://acme.test/entity/', new BasicEntityIdParser() );

		$this->assertEquals( new ItemId( 'Q14' ), $parser->parse( 'http://acme.test/entity/Q14' ) );
		$this->assertEquals( new ItemId( 'Q14' ), $parser->parse( 'http://acme.test/entity/Q14' ), 'HTTPS' );

		$this->setExpectedException( 'Wikibase\DataModel\Entity\EntityIdParsingException' );
		$parser->parse( 'http://acme.test/Q14' );
	}

}
