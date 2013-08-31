<?php

namespace Wikibase\Test;

use Wikibase\ChangeOpClaim;
use Wikibase\Claim;
use Wikibase\Claims;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Entity;
use Wikibase\Item;
use Wikibase\ItemContent;
use InvalidArgumentException;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\SnakObject;

/**
 * @covers Wikibase\ChangeOpClaim
 *
 * @file
 * @since 0.4
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ChangeOpClaimTest extends \PHPUnit_Framework_TestCase {

	public function invalidConstructorProvider() {
		$validGuidGenerator = new ClaimGuidGenerator( ItemId::newFromNumber( 42 ) );

		$args = array();
		$args[] = array( 42, 'add', $validGuidGenerator );
		$args[] = array( 'en', 'remove', $validGuidGenerator );
		$args[] = array( array(), 'remove', $validGuidGenerator );
		return $args;
	}

	/**
	 * @dataProvider invalidConstructorProvider
	 * @expectedException InvalidArgumentException
	 *
	 * @param Claim $claim
	 * @param string $action
	 * @param ClaimGuidGenerator $guidGenerator
	 */
	public function testInvalidConstruct( $claim, $action, $guidGenerator) {
		$changeOp = new ChangeOpClaim( $claim, $action, $guidGenerator);
	}

	public function provideTestApply() {
		$itemEmpty = Item::newEmpty();
		$itemEmpty->setId( ItemId::newFromNumber( 888 ) );
		$item777 = self::provideNewItemWithClaim( 777, new PropertyNoValueSnak( 45 ) );
		$item666 = self::provideNewItemWithClaim( 666, new PropertySomeValueSnak( 44 ) );

		$claims[0] = new Claim( new PropertyNoValueSnak( 43 ) );
		$item777Claims = $item777->getClaims();
		$claims[777] = clone $item777Claims[0];
		$item666Claims = $item666->getClaims();
		$claims[666] = clone $item666Claims[0];

		$args = array();
		//test adding claims with guids from other items
		$args[] = array ( $itemEmpty, clone $claims[666] , 'add' , false );
		$args[] = array ( $itemEmpty, clone $claims[777] , 'add' , false );
		$args[] = array ( $item666, clone $claims[777] , 'add' , false );
		$args[] = array ( $item777, clone $claims[666] , 'add' , false );
		//test adding claims with from this item
		$args[] = array ( $item777, clone $claims[777] , 'remove' , array( ) );
		$args[] = array ( $item777, clone $claims[777] , 'add' , array( clone $claims[777] ) );
		$args[] = array ( $item666, clone $claims[666] , 'remove' , array( ) );
		$args[] = array ( $item666, clone $claims[666] , 'add' , array( clone $claims[666] ) );
		//test adding claims with no guid
		$args[] = array ( $itemEmpty, clone $claims[0] , 'add' , array( clone $claims[0] ) );
		$args[] = array ( $item777, clone $claims[0] , 'add' , array( clone $claims[777], clone $claims[0] ) );
		$args[] = array ( $item666, clone $claims[0] , 'add' , array( clone $claims[666], clone $claims[0] ) );
		//test removing claims with good guids that exist
		$args[] = array ( $item777, clone $claims[777] , 'remove' , array( clone $claims[0] ) );
		$args[] = array ( $item666, clone $claims[666] , 'remove' , array( clone $claims[0] ) );
		//test removing claims with good guids that dont exist
		$args[] = array ( $item777, clone $claims[777] , 'remove' , false );
		$args[] = array ( $item666, clone $claims[666] , 'remove' , false );
		//test removing claim with no guid
		$args[] = array ( $itemEmpty, clone $claims[0] , 'remove' , false );

		return $args;
	}

	/**
	 * @dataProvider provideTestApply
	 *
	 * @param Entity $entity
	 * @param Claim $claim
	 * @param string $action
	 * @param Claim[]|bool $expected
	 */
	public function testApply( $entity, $claim, $action, $expected ) {
		if( $expected === false ){
			$this->setExpectedException( '\Wikibase\ChangeOpException' );
		}

		$changeOpClaim = new ChangeOpClaim( $claim, $action, new ClaimGuidGenerator( $entity->getId() ) );
		$changeOpClaim->apply( $entity );

		if( $expected === false ){
			$this->fail( 'Failed to throw a ChangeOpException' );
		}

		$entityClaims = new Claims( $entity->getClaims() );
		foreach( $expected as $expectedClaim ){
			$this->assertTrue( $entityClaims->hasClaim( $expectedClaim ) );
		}
		$this->assertEquals( count( $expected ), $entityClaims->count() );
	}

	/**
	 * @expectedException \Wikibase\ChangeOpException
	 */
	public function testApplyWithInvalidAction() {
		$item = ItemContent::newEmpty();
		$entity = $item->getEntity();

		$changeOpClaim = new ChangeOpClaim(
			new Claim( new PropertyNoValueSnak( 43 ) ) ,
			'invalidAction',
			new ClaimGuidGenerator( ItemId::newFromNumber( 42 ) ) );

		$changeOpClaim->apply( $entity );
	}


	/**
	 * @param integer $itemId
	 * @param $snak
	 * @return Item
	 */
	protected function provideNewItemWithClaim( $itemId, $snak ) {
		$entity = Item::newEmpty();
		$entity->setId( ItemId::newFromNumber( $itemId ) );

		$claim = $entity->newClaim( $snak );
		$guidGenerator = new ClaimGuidGenerator( $entity->getId() );
		$claim->setGuid( $guidGenerator->newGuid() );

		$claims = new Claims();
		$claims->addClaim( $claim );
		$entity->setClaims( $claims );

		return $entity;
	}

}
