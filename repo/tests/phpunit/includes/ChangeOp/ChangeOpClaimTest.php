<?php

namespace Wikibase\Test;

use Wikibase\ChangeOp\ChangeOpClaim;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\ClaimGuid;
use Wikibase\DataModel\Claim\ClaimGuidParser;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use InvalidArgumentException;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Lib\ClaimGuidValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\ChangeOp\ChangeOpClaim
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 * @group ChangeOpClaim
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ChangeOpClaimTest extends \PHPUnit_Framework_TestCase {

	public function getValidClaim() {
		return new Claim( new PropertyNoValueSnak( 7 ) );
	}

	public function getValidGuidGenerator( ItemId $itemId ) {
		return new ClaimGuidGenerator( $itemId );
	}

	private function getMockGuidValidator() {
		$mock = $this->getMockBuilder( '\Wikibase\Lib\ClaimGuidValidator' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'validate' )
			->will( $this->returnValue( true ) );
		$mock->expects( $this->any() )
			->method( 'validateFormat' )
			->will( $this->returnValue( true ) );
		return $mock;
	}

	private function getMockGuidParser( ItemId $itemId ) {
		$mockClaimGuid = $this->getMockBuilder( 'Wikibase\DataModel\Claim\ClaimGuid' )
			->disableOriginalConstructor()
			->getMock();
		$mockClaimGuid->expects( $this->any() )
			->method( 'getSerialization' )
			->will( $this->returnValue( 'theValidatorIsMockedSoMeh! :D' ) );
		$mockClaimGuid->expects( $this->any() )
			->method( 'getEntityId' )
			->will( $this->returnValue( $itemId ) );

		$mock = $this->getMockBuilder( 'Wikibase\DataModel\Claim\ClaimGuidParser' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'parse' )
			->will( $this->returnValue( $mockClaimGuid ) );
		return $mock;
	}

	public function invalidIndexProvider() {
		return array(
			array( 'foo' ),
			array( array() ),
			array( $this->getValidClaim() ),
		);
	}

	/**
	 * @dataProvider invalidIndexProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testConstructionWithInvalidIndex( $invalidIndex ) {
		$itemId = new ItemId( 'q42' );
		new ChangeOpClaim(
			$this->getValidClaim(),
			$this->getValidGuidGenerator( $itemId),
			$this->getMockGuidValidator(),
			$this->getMockGuidParser( $itemId ),
			$invalidIndex
		);
	}

	public function provideTestApply() {
		$itemEmpty = Item::newEmpty();
		$itemEmpty->setId( new ItemId( 'q888' ) );
		$item777 = self::makeNewItemWithClaim( 'Q777', new PropertyNoValueSnak( 45 ) );
		$item666 = self::makeNewItemWithClaim( 'Q666', new PropertySomeValueSnak( 44 ) );

		$item777Claims = $item777->getClaims();
		$item666Claims = $item666->getClaims();

		$claim777 = reset( $item777Claims );
		$claim666 = reset( $item666Claims );

		//claims that exist on the given entities
		$claims[0] = new Claim( new PropertyNoValueSnak( 43 ) );
		$claims[777] = clone $claim777;
		$claims[666] = clone $claim666;
		//claims with a null guid
		$claims[7770] = clone $claim777;
		$claims[7770]->setGuid( null );
		$claims[6660] = clone $claim666;
		$claims[6660]->setGuid( null );
		//new claims not yet on the entity
		$claims[7777] = clone $claim777;
		$claims[7777]->setGuid( 'Q777$D8404CDA-25E4-4334-AF13-A3290BC77777' );
		$claims[6666] = clone $claim666;
		$claims[6666]->setGuid( 'Q666$D8404CDA-25E4-4334-AF13-A3290BC66666' );

		$claims[11] = new Claim( new PropertyNoValueSnak( 1 ) );
		$claims[11]->setGuid( null );
		$claims[12] = new Claim( new PropertySomeValueSnak( 1 ) );
		$claims[12]->setGuid( null );
		$claims[13] = clone $claims[12];
		$claims[13]->setGuid( 'Q666$D8404CDA-25E4-4334-AF13-A3290BC66613' );

		$args = array();
		//test adding claims with guids from other items(these shouldn't be added)
		$args[] = array( $itemEmpty, $claims[666], false );
		$args[] = array( $itemEmpty, $claims[777], false );
		$args[] = array( $item666, $claims[777], false );
		$args[] = array( $item777, $claims[666], false );
		//test adding the same claims with a null guid (a guid should be created)
		$args[] = array( $item777, $claims[7770], array( $claims[777], $claims[7770] ) );
		$args[] = array( $item666, $claims[6660], array( $claims[666], $claims[6660] ) );
		//test adding the same claims with a correct but different guid (these should be added)
		$args[] = array( $item777, $claims[7777], array( $claims[777], $claims[7770], $claims[7777] ) );
		$args[] = array( $item666, $claims[6666], array( $claims[666], $claims[6660], $claims[6666] ) );
		//test adding the same claims with and id that already exists (these shouldn't be added)
		$args[] = array( $item777, $claims[7777], array( $claims[777], $claims[7770], $claims[7777] ) );
		$args[] = array( $item666, $claims[6666], array( $claims[666], $claims[6660], $claims[6666] ) );
		// test adding a claim at a specific index
		$args[] = array( $item777, $claims[0], array( $claims[0], $claims[777], $claims[7770], $claims[7777] ), 0 );
		// test moving a claim
		$args[] = array( $item666, $claims[6666], array( $claims[666], $claims[6666], $claims[6660] ), 1 );
		// test adding a claim featuring another property id within the boundaries of claims the
		// same property
		$args[] = array( $item666, $claims[11], array( $claims[666], $claims[6666], $claims[6660], $claims[11] ), 1 );
		// test moving a subset of claims featuring the same property
		$args[] = array( $item666, $claims[12], array( $claims[12], $claims[11], $claims[666], $claims[6666], $claims[6660] ), 0 );

		return $args;
	}

	/**
	 * @dataProvider provideTestApply
	 *
	 * @param Entity $entity
	 * @param Claim $claim
	 * @param Claim[]|bool $expected
	 * @param int|null $index
	 */
	public function testApply( $entity, $claim, $expected, $index = null ) {
		if( $expected === false ){
			$this->setExpectedException( '\Wikibase\ChangeOp\ChangeOpException' );
		}

		$changeOpClaim = new ChangeOpClaim(
			$claim,
			new ClaimGuidGenerator( $entity->getId() ),
			WikibaseRepo::getDefaultInstance()->getClaimGuidValidator(), //@todo mock me!
			WikibaseRepo::getDefaultInstance()->getClaimGuidParser(), //@todo mock me!
			$index
		);
		$changeOpClaim->apply( $entity );

		if( $expected === false ){
			$this->fail( 'Failed to throw a ChangeOpException' );
		}

		$entityClaims = new Claims( $entity->getClaims() );
		$entityClaimHashSet = array_flip( $entityClaims->getHashes() );
		$i = 0;

		foreach( $expected as $expectedClaim ){
			$guid = $expectedClaim->getGuid();
			$hash = $expectedClaim->getHash();

			if ( $guid !== null ) {
				$this->assertEquals( $i++, $entityClaims->indexOf( $expectedClaim ) );
			}

			$this->assertArrayHasKey( $hash, $entityClaimHashSet );
		}

		$this->assertEquals( count( $expected ), $entityClaims->count() );
	}

	public function provideInvalidApply() {
		/* @var Claim $claim */

		$snak = new PropertyNoValueSnak( 67573284 );
		$item = $this->makeNewItemWithClaim( 'Q777', $snak );
		$claims = $item->getClaims();
		$claim = reset( $claims );
		$guidGenerator = new ClaimGuidGenerator( $item->getId() );

		// change main snak to "some value"
		$newSnak = new PropertySomeValueSnak( 67573284 );
		$newClaim = clone $claim;
		$newClaim->setMainSnak( $newSnak );

		// apply change to the wrong item
		$wrongItem = Item::newEmpty();
		$wrongItem->setId( new ItemId( "Q888" ) );
		$args['wrong entity'] = array ( $wrongItem, new ChangeOpClaim( $newClaim, $guidGenerator ) );

		//TODO: once we stop allowing user-generated GUIDs for new claims, test this below.
		// apply change to an unknown claim
		/*
		$wrongClaimId = $item->getId()->getPrefixedId() . '$DEADBEEF-DEAD-BEEF-DEAD-BEEFDEADBEEF';
		$badClaim = clone $newClaim;
		$badClaim->setGuid( $wrongClaimId );
		$args['unknown claim'] = array ( $item, new ChangeOpClaim( $badClaim, $guidGenerator ) );
		*/

		// update an existing claim with wrong main snak property
		$newSnak = new PropertyNoValueSnak( 23452345 );
		$newClaim->setMainSnak( $newSnak );

		$changeOp = new ChangeOpClaim( $newClaim, $guidGenerator );
		$args['wrong main snak property'] = array ( $item, $changeOp );

		return $args;
	}

	/**
	 * @dataProvider provideInvalidApply
	 */
	public function testInvalidApply( Entity $item, ChangeOpClaim $changeOp ) {
		$this->setExpectedException( 'Wikibase\ChangeOp\ChangeOpException' );

		$changeOp->apply( $item );
	}

	/**
	 * @param integer $itemId
	 * @param $snak
	 * @return Item
	 */
	protected function makeNewItemWithClaim( $itemId, $snak ) {
		$entity = Item::newEmpty();
		$entity->setId( new ItemId( $itemId ) );

		$claim = $entity->newClaim( $snak );
		$guidGenerator = new ClaimGuidGenerator( $entity->getId() );
		$claim->setGuid( $guidGenerator->newGuid() );

		$claims = new Claims();
		$claims->addClaim( $claim );
		$entity->setClaims( $claims );

		return $entity;
	}

}
