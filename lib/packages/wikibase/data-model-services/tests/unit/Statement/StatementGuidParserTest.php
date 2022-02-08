<?php

namespace Wikibase\DataModel\Services\Tests\Statement;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParsingException;
use Wikibase\DataModel\Statement\StatementGuid;

/**
 * @covers \Wikibase\DataModel\Services\Statement\StatementGuidParser
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class StatementGuidParserTest extends TestCase {

	/**
	 * @dataProvider guidProvider
	 */
	public function testCanParseStatementGuid( StatementGuid $expected ) {
		$actual = $this->newParser()->parse( $expected->getSerialization() );

		$this->assertEquals( $expected, $actual );
	}

	private function newParser() {
		return new StatementGuidParser( new ItemIdParser() );
	}

	public function guidProvider() {
		return [
			[ new StatementGuid( new ItemId( 'q42' ), 'D8404CDA-25E4-4334-AF13-A3290BCD9C0N' ) ],
			[ new StatementGuid( new ItemId( 'Q1234567' ), 'D4FDE516-F20C-4154-ADCE-7C5B609DFDFF' ) ],
			[ new StatementGuid( new ItemId( 'Q1' ), 'foo' ) ],
			[ new StatementGuid( new ItemId( 'Q1' ), '$' ) ],
			[ new StatementGuid( new ItemId( 'Q1' ), '' ) ],
		];
	}

	/**
	 * @dataProvider invalidIdSerializationProvider
	 */
	public function testCannotParserInvalidId( $invalidIdSerialization ) {
		$this->expectException( StatementGuidParsingException::class );
		$this->newParser()->parse( $invalidIdSerialization );
	}

	public function invalidIdSerializationProvider() {
		return [
			[ 'FOO' ],
			[ null ],
			[ 42 ],
			[ [] ],
			[ '' ],
			[ 'q0' ],
			[ '1p' ],
			[ 'Q0$5627445f-43cb-ed6d-3adb-760e85bd17ee' ],
			[ 'Q1' ],
		];
	}

}
