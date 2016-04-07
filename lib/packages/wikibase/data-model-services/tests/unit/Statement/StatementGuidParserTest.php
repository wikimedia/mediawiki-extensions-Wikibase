<?php

namespace Wikibase\DataModel\Services\Tests\Statement;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\StatementGuid;

/**
 * @covers Wikibase\DataModel\Services\Statement\StatementGuidParser
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class StatementGuidParserTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider guidProvider
	 */
	public function testCanParseStatementGuid( StatementGuid $expected ) {
		$actual = $this->newParser()->parse( $expected->getSerialization() );

		$this->assertEquals( $actual, $expected );
	}

	protected function newParser() {
		return new StatementGuidParser( new BasicEntityIdParser() );
	}

	public function guidProvider() {
		return array(
			array( new StatementGuid( new ItemId( 'q42' ), 'D8404CDA-25E4-4334-AF13-A3290BCD9C0N' ) ),
			array( new StatementGuid( new ItemId( 'Q1234567' ), 'D4FDE516-F20C-4154-ADCE-7C5B609DFDFF' ) ),
			array( new StatementGuid( new ItemId( 'Q1' ), 'foo' ) ),
			array( new StatementGuid( new ItemId( 'Q1' ), '$' ) ),
			array( new StatementGuid( new ItemId( 'Q1' ), '' ) ),
		);
	}

	/**
	 * @dataProvider invalidIdSerializationProvider
	 */
	public function testCannotParserInvalidId( $invalidIdSerialization ) {
		$this->setExpectedException( 'Wikibase\DataModel\Services\Statement\StatementGuidParsingException' );
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
			array( 'Q0$5627445f-43cb-ed6d-3adb-760e85bd17ee' ),
			array( 'Q1' ),
		);
	}

}
