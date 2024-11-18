<?php declare( strict_types=1 );

namespace Wikibase\DataModel\Tests\Statement;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementGuid;

/**
 * @covers \Wikibase\DataModel\Statement\StatementGuid
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class StatementGuidTest extends TestCase {

	/**
	 * @dataProvider provideValidConstructionData
	 */
	public function testConstructor( EntityId $entityId, string $guidPart, ?string $originalSerialization, string $expected ): void {
		$statementGuid = new StatementGuid( $entityId, $guidPart, $originalSerialization );

		$this->assertSame( $expected, $statementGuid->getSerialization() );
		$this->assertEquals( $entityId, $statementGuid->getEntityId() );
		$this->assertSame( $guidPart, $statementGuid->getGuidPart() );
	}

	public static function provideValidConstructionData(): array {
		return [
			[
				new ItemId( 'q42' ),
				'D8404CDA-25E4-4334-AF13-A3290BCD9C0N',
				null,
				'Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0N',
			],
			[
				new ItemId( 'q42' ),
				'D8404CDA-25E4-4334-AF13-A3290BCD9C0N',
				'q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0N',
				'q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0N',
			],
			[
				new ItemId( 'Q1234567' ),
				'D4FDE516-F20C-4154-ADCE-7C5B609DFDFF',
				null,
				'Q1234567$D4FDE516-F20C-4154-ADCE-7C5B609DFDFF',
			],
			[
				new ItemId( 'Q1234567' ),
				'D4FDE516-F20C-4154-ADCE-7C5B609DFDFF',
				'Q1234567$D4FDE516-F20C-4154-ADCE-7C5B609DFDFF',
				'Q1234567$D4FDE516-F20C-4154-ADCE-7C5B609DFDFF',
			],
			[
				new ItemId( 'Q1' ),
				'foo',
				null,
				'Q1$foo',
			],
			[
				new ItemId( 'Q1' ),
				'foo',
				'Q1$foo',
				'Q1$foo',
			],
		];
	}

	public static function provideStatementGuids(): array {
		$argLists = [];

		foreach ( self::provideValidConstructionData() as $data ) {
			$argLists[] = [ new StatementGuid( ...$data ) ];
		}

		return $argLists;
	}

	/**
	 * @dataProvider provideStatementGuids
	 */
	public function testEquals( StatementGuid $statementGuid ): void {
		$statementGuidCopy = clone $statementGuid;
		$this->assertTrue( $statementGuid->equals( $statementGuidCopy ) );
		$this->assertTrue( $statementGuidCopy->equals( $statementGuid ) );
	}

	/**
	 * @dataProvider provideStatementGuids
	 */
	public function testNotEquals( StatementGuid $statementGuid ): void {
		$notEqualStatementGuid = new StatementGuid( new ItemId( 'q9999' ), 'someguid' );
		$this->assertFalse( $statementGuid->equals( $notEqualStatementGuid ) );
		$this->assertFalse( $notEqualStatementGuid->equals( $statementGuid ) );
	}

}
