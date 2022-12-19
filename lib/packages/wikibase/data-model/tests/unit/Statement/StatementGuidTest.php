<?php

namespace Wikibase\DataModel\Tests\Statement;

use InvalidArgumentException;
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
class StatementGuidTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider provideConstructionData
	 */
	public function testConstructor( EntityId $entityId, $guid, $expected ) {
		$statementGuid = new StatementGuid( $entityId, $guid );

		$this->assertSame( $expected, $statementGuid->getSerialization() );
		$this->assertEquals( $entityId, $statementGuid->getEntityId() );
		$this->assertSame( $guid, $statementGuid->getGuidPart() );
	}

	public function provideConstructionData() {
		return [
			[
				new ItemId( 'q42' ),
				'D8404CDA-25E4-4334-AF13-A3290BCD9C0N' ,
				'Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0N',
			],
			[
				new ItemId( 'Q1234567' ),
				'D4FDE516-F20C-4154-ADCE-7C5B609DFDFF',
				'Q1234567$D4FDE516-F20C-4154-ADCE-7C5B609DFDFF',
			],
			[
				new ItemId( 'Q1' ),
				'foo',
				'Q1$foo',
			],
		];
	}

	/**
	 * @dataProvider provideBadConstruction
	 */
	public function testBadConstruction( EntityId $entityId, $guid ) {
		$this->expectException( InvalidArgumentException::class );
		new StatementGuid( $entityId, $guid );
	}

	public function provideBadConstruction() {
		$id = new ItemId( 'Q1' );

		return [
			[ $id, null ],
			[ $id, 12345 ],
		];
	}

	public function provideStatementGuids() {
		$argLists = [];

		foreach ( $this->provideConstructionData() as $data ) {
			$argLists[] = [ new StatementGuid( $data[0], $data[1] ) ];
		}

		return $argLists;
	}

	/**
	 * @dataProvider provideStatementGuids
	 */
	public function testEquals( StatementGuid $statementGuid ) {
		$statementGuidCopy = clone $statementGuid;
		$this->assertTrue( $statementGuid->equals( $statementGuidCopy ) );
		$this->assertTrue( $statementGuidCopy->equals( $statementGuid ) );
	}

	/**
	 * @dataProvider provideStatementGuids
	 */
	public function testNotEquals( StatementGuid $statementGuid ) {
		$notEqualStatementGuid = new StatementGuid( new ItemId( 'q9999' ), 'someguid' );
		$this->assertFalse( $statementGuid->equals( $notEqualStatementGuid ) );
		$this->assertFalse( $notEqualStatementGuid->equals( $statementGuid ) );
	}

}
