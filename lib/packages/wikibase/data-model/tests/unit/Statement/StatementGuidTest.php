<?php

namespace Wikibase\DataModel\Tests\Statement;

use Exception;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\DataModel\Statement\StatementGuid
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class StatementGuidTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider provideConstructionData
	 * @param EntityId $entityId
	 * @param string $guid
	 * @param string $expected
	 */
	public function testConstructor( EntityId $entityId, $guid, $expected ) {
		$statementGuid = new StatementGuid( $entityId, $guid );

		$this->assertEquals( $expected, $statementGuid->getSerialization() );
		$this->assertEquals( $entityId, $statementGuid->getEntityId() );
	}

	public function provideConstructionData() {
		$argLists = [];

		$argLists[] = [
			new ItemId( 'q42' ),
			'D8404CDA-25E4-4334-AF13-A3290BCD9C0N' ,
			'Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0N'
		];
		$argLists[] = [
			new ItemId( 'Q1234567' ),
			'D4FDE516-F20C-4154-ADCE-7C5B609DFDFF',
			'Q1234567$D4FDE516-F20C-4154-ADCE-7C5B609DFDFF'
		];
		$argLists[] = [
			new ItemId( 'Q1' ),
			'foo',
			'Q1$foo'
		];

		return $argLists;
	}

	/**
	 * @dataProvider provideBadConstruction
	 */
	public function testBadConstruction( $entityId, $guid ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		new StatementGuid( $entityId, $guid );
	}

	public function provideBadConstruction() {
		$argLists = [];

		$argLists[] = [ 'foobar', 'foobar' ];
		$argLists[] = [ 'q123', 'foo' ];
		$argLists[] = [ [], 'foo' ];
		$argLists[] = [ new Exception(), 'foo' ];
		$argLists[] = [ 'bar', 12345 ];

		return $argLists;
	}

	public function provideStatementGuids() {
		$constructionDatas = $this->provideConstructionData();
		$argLists = [];

		foreach ( $constructionDatas as $constructionData ) {
			$argLists[] = [ new StatementGuid( $constructionData[0], $constructionData[1] ) ];
		}

		return $argLists;
	}

	/**
	 * @dataProvider provideStatementGuids
	 *
	 * @param StatementGuid $statementGuid
	 */
	public function testEquals( StatementGuid $statementGuid ) {
		$statementGuidCopy = clone $statementGuid;
		$this->assertTrue( $statementGuid->equals( $statementGuidCopy ) );
		$this->assertTrue( $statementGuidCopy->equals( $statementGuid ) );
	}

	/**
	 * @dataProvider provideStatementGuids
	 *
	 * @param StatementGuid $statementGuid
	 */
	public function testNotEquals( StatementGuid $statementGuid ) {
		$notEqualStatementGuid = new StatementGuid( new ItemId( 'q9999' ), 'someguid' );
		$this->assertFalse( $statementGuid->equals( $notEqualStatementGuid ) );
		$this->assertFalse( $notEqualStatementGuid->equals( $statementGuid ) );
	}

}
