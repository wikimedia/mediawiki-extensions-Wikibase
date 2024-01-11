<?php declare( strict_types=1 );

namespace Wikibase\DataModel\Services\Tests\Statement;

use Generator;
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
	public function testCanParseStatementGuid( string $serialization, StatementGuid $expected ): void {
		$actual = $this->newParser()->parse( $serialization );
		$this->assertEquals( $expected, $actual );
		$this->assertEquals( $serialization, $actual->getSerialization() );
		$this->assertTrue( $expected->equals( $actual ) );
	}

	private function newParser(): StatementGuidParser {
		return new StatementGuidParser( new ItemIdParser() );
	}

	public static function guidProvider(): Generator {
		$serialization = 'q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0N';
		yield [ $serialization, new StatementGuid( new ItemId( 'q42' ), 'D8404CDA-25E4-4334-AF13-A3290BCD9C0N', $serialization ) ];

		$serialization = 'Q1$foo';
		yield [ $serialization, new StatementGuid( new ItemId( 'Q1' ), 'foo', $serialization ) ];

		$serialization = 'Q1$$';
		yield [ $serialization, new StatementGuid( new ItemId( 'Q1' ), '$', $serialization ) ];

		$serialization = 'Q1234567$D4FDE516-F20C-4154-ADCE-7C5B609DFDFF';
		yield [ $serialization, new StatementGuid( new ItemId( 'Q1234567' ), 'D4FDE516-F20C-4154-ADCE-7C5B609DFDFF', $serialization ) ];

		$serialization = 'Q1$';
		yield [ $serialization, new StatementGuid( new ItemId( 'Q1' ), '', $serialization ) ];
	}

	/**
	 * @dataProvider invalidIdSerializationProvider
	 */
	public function testCannotParserInvalidId( string $invalidIdSerialization ): void {
		$this->expectException( StatementGuidParsingException::class );
		$this->newParser()->parse( $invalidIdSerialization );
	}

	public static function invalidIdSerializationProvider(): array {
		return [
			[ 'FOO' ],
			[ '' ],
			[ 'q0' ],
			[ '1p' ],
			[ 'Q0$5627445f-43cb-ed6d-3adb-760e85bd17ee' ],
			[ 'Q1' ],
		];
	}

}
