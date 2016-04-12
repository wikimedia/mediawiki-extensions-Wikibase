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
 * @group ClaimGuidTest
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
		$argLists = array();

		$argLists[] = array(
			new ItemId( 'q42' ),
			'D8404CDA-25E4-4334-AF13-A3290BCD9C0N' ,
			'Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0N'
		);
		$argLists[] = array(
			new ItemId( 'Q1234567' ),
			'D4FDE516-F20C-4154-ADCE-7C5B609DFDFF',
			'Q1234567$D4FDE516-F20C-4154-ADCE-7C5B609DFDFF'
		);
		$argLists[] = array(
			new ItemId( 'Q1' ),
			'foo',
			'Q1$foo'
		);

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
		$argLists = array();

		$argLists[] = array( 'foobar', 'foobar' );
		$argLists[] = array( 'q123', 'foo' );
		$argLists[] = array( array(), 'foo' );
		$argLists[] = array( new Exception(), 'foo' );
		$argLists[] = array( 'bar', 12345 );

		return $argLists;
	}

	public function provideStatementGuids() {
		$constructionDatas = $this->provideConstructionData();
		$argLists = array();

		foreach ( $constructionDatas as $constructionData ) {
			$argLists[] = array( new StatementGuid( $constructionData[0], $constructionData[1] ) );
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
