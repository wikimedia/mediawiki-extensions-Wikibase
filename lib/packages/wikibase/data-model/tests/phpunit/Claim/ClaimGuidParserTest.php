<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Claim\ClaimGuid;
use Wikibase\DataModel\Claim\ClaimGuidParser;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\DataModel\Claim\ClaimGuidParser
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ClaimGuidParserTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider guidProvider
	 */
	public function testCanParseClaimGuid( ClaimGuid $expected ){
		$actual = $this->newParser()->parse( $expected->getSerialization() );

		$this->assertEquals( $actual, $expected );
	}

	protected function newParser() {
		return new ClaimGuidParser( new BasicEntityIdParser() );
	}

	public function guidProvider() {
		return array(
			array( new ClaimGuid( new ItemId( 'q42' ), 'D8404CDA-25E4-4334-AF13-A3290BCD9C0N' ) ),
			array( new ClaimGuid( new ItemId( 'Q1234567' ), 'D4FDE516-F20C-4154-ADCE-7C5B609DFDFF' ) ),
			array( new ClaimGuid( new ItemId( 'Q1' ), 'foo' ) ),
		);
	}

	/**
	 * @dataProvider invalidIdSerializationProvider
	 */
	public function testCannotParserInvalidId( $invalidIdSerialization ) {
		$this->setExpectedException( 'Wikibase\DataModel\Claim\ClaimGuidParsingException' );
		$this->newParser()->parse( $invalidIdSerialization );
	}

	public function invalidIdSerializationProvider() {
		return array(
			array( 'FOO' ),
			array( null ),
			array( 42 ),
			array( array() ),
			array( '' ),
			array( 'q0' ),
			array( '1p' ),
		);
	}

}
