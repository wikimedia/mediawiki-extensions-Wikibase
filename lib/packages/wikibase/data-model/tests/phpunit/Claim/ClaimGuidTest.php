<?php

namespace Wikibase\Test;

use Exception;
use Wikibase\DataModel\Claim\ClaimGuid;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Item;
use Wikibase\Property;

/**
 * @covers Wikibase\DataModel\Claim\ClaimGuid
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group ClaimGuidTest
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ClaimGuidTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider provideConstructionData
	 *
	 * @param $entityId
	 * @param $guid
	 * @param $expected
	 */
	public function testConstructor( $entityId, $guid, $expected ) {
		$claimGuid = new ClaimGuid( $entityId, $guid );

		$this->assertEquals( $expected, $claimGuid->getSerialization() );
		$this->assertEquals( $entityId, $claimGuid->getEntityId());
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
	 *
	 * @param $entityId
	 * @param $guid
	 */
	public function testBadConstruction( $entityId, $guid ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		$claimGuid = new ClaimGuid( $entityId,$guid );
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

	public function provideClaimGuids() {
		$constructionDatas = $this->provideConstructionData();
		$argLists = array();

		foreach( $constructionDatas as $constructionData ){
			$argLists[] = array( new ClaimGuid( $constructionData[0], $constructionData[1] ) );
		}

		return $argLists;
	}

	/**
	 * @dataProvider provideClaimGuids
	 *
	 * @param ClaimGuid $claimGuid
	 */
	public function testEquals( $claimGuid ) {
		$claimGuidCopy = clone $claimGuid;
		$this->assertTrue( $claimGuid->equals( $claimGuidCopy ) );
		$this->assertTrue( $claimGuidCopy->equals( $claimGuid ) );
	}

	/**
	 * @dataProvider provideClaimGuids
	 *
	 * @param ClaimGuid $claimGuid
	 */
	public function testNotEquals( $claimGuid ) {
		$notEqualClaimGuid = new ClaimGuid( new ItemId( 'q9999' ), 'someguid' );
		$this->assertFalse( $claimGuid->equals( $notEqualClaimGuid ) );
		$this->assertFalse( $notEqualClaimGuid->equals( $claimGuid ) );
	}

}
