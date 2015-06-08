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
			'Simple base URI' => array( '!^https?://acme.test/entity/(.*)$!', 'http://acme.test/entity/Q14', new ItemId( 'Q14' ) ),
			'Strip fragment ID' => array( '!^(?:https?)://acme.test/entity/(.*?)(#.*)?$!', 'http://acme.test/entity/P14', new PropertyId( 'P14' ) ),
			'Local ID, no prefix' => array( '!^([QP]\d+)$!', 'P14', new PropertyId( 'P14' ) ),
		);
	}

	/**
	 * @dataProvider provideParse
	 */
	public function testParse( $regexp, $input, $expected ) {
		$parser = new ExtractingEntityIdParser( $regexp, new BasicEntityIdParser() );
		$this->assertEquals( $expected, $parser->parse( $input ) );
	}

	public function provideParse_invalid() {
		return array(
			'mismatching prefix' => array( '!^https?://acme.test/entity/(.*)$!', 'http://acme.test/Q14' ),
			'bad ID after prefix' => array( '!^https?://acme.test/entity/(.*)$!', 'http://acme.test/entity/XYYZ' ),
			'unexpected fragment id' => array( '!^https?://acme.test/entity/(.*)$!', 'http://acme.test/Q14#x' ),
			'missing prefix' => array( '!^https?://acme.test/entity/(.*)$!', 'Q14' ),
			'prefix with no capture group' => array( '!^https?://acme.test/entity/.*$!', 'http://acme.test/entity/Q14' ),
			'ID pattern with no capture group' => array( '!^[QP]\d+$!', 'P14', new PropertyId( 'P14' ) ),
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
