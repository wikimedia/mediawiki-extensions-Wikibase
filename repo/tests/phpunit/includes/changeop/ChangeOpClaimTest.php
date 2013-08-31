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
use Wikibase\ItemModificationUpdate;
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
		$args = array();
		$args[] = array( 42, 'add' );
		$args[] = array( 'en', 'remove' );
		$args[] = array( array(), 'remove' );
		return $args;
	}

	/**
	 * @dataProvider invalidConstructorProvider
	 * @expectedException InvalidArgumentException
	 *
	 * @param Claim $claim
	 * @param string $action
	 */
	public function testInvalidConstruct( $claim, $action ) {
		$changeOp = new ChangeOpClaim( $claim, $action );
	}

	public function provideTestApply() {
		$itemEmpty = Item::newEmpty();
		$itemEmpty->setId( ItemId::newFromNumber( 888 ) );
		$item777 = self::provideNewItemWithClaim( 777, new PropertyNoValueSnak( 45 ) );
		$item666 = self::provideNewItemWithClaim( 666, new PropertySomeValueSnak( 44 ) );

		$claims[0] = new Claim( new PropertyNoValueSnak( 43 ) );//0
		$claims = array_merge(
			$claims ,//0
			$item777->getClaims(),//1
			$item666->getClaims()//2
		);

		$args = array();
		//test adding claims
		$args[] = array ( $itemEmpty, clone $claims[0] , 'add' , array( clone $claims[0] ) );
		$args[] = array ( $itemEmpty, clone $claims[1] , 'add' , array( clone $claims[0], clone $claims[1] ) );
		//test removing and re adding claims
		$args[] = array ( $item777, clone $claims[1] , 'remove' , array( ) );
		$args[] = array ( $item777, clone $claims[1] , 'add' , array( clone $claims[1] ) );
		$args[] = array ( $item666, clone $claims[2] , 'remove' , array( ) );
		$args[] = array ( $item666, clone $claims[2] , 'add' , array( clone $claims[2] ) );

		return $args;
	}

	/**
	 * @dataProvider provideTestApply
	 *
	 * @param Entity $entity
	 * @param Claim $claim
	 * @param string $action
	 * @param Claim[] $expectedClaims
	 */
	public function testApply( $entity, $claim, $action, $expectedClaims ) {
		$changeOpClaim = new ChangeOpClaim( $claim, $action );
		$changeOpClaim->apply( $entity );
		$entityClaims = new Claims( $entity->getClaims() );
		foreach( $expectedClaims as $expectedClaim ){
			$this->assertTrue( $entityClaims->hasClaim( $expectedClaim ) );
		}
		$this->assertEquals( count( $expectedClaims ), $entityClaims->count() );
	}

	/**
	 * @expectedException \Wikibase\ChangeOpException
	 */
	public function testApplyWithInvalidAction() {
		$item = ItemContent::newEmpty();
		$entity = $item->getEntity();
		$changeOpClaim = new ChangeOpClaim( new Claim( new PropertyNoValueSnak( 43 ) ) , 'invalidAction'  );
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
		$claims = new Claims();
		$claims->addClaim( $claim );
		$entity->setClaims( $claims );

		return $entity;
	}

}
