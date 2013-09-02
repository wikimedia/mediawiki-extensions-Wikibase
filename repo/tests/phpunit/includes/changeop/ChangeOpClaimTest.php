<?php

namespace Wikibase\Test;

use Wikibase\ChangeOpClaim;
use Wikibase\Claim;
use Wikibase\Claims;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Entity;
use Wikibase\EntityId;
use Wikibase\ItemContent;
use InvalidArgumentException;
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

	public function changeOpClaimProvider() {
		$noValueClaim = new Claim( new PropertyNoValueSnak( 43 ) );

		$differentEntity = ItemContent::newEmpty()->getEntity();
		$differentEntity->setId( ItemId::newFromNumber( 777 ) );
		$oldNoValueClaim = new Claim( new PropertyNoValueSnak( 43 ) );
		$oldNoValueClaim->setGuid( $differentEntity->getId()->getPrefixedId() . '$D8404XXA-25E4-4004-AG93-A3290BCD9C0P' );

		$entity = ItemContent::newEmpty()->getEntity();
		$entity->setId( ItemId::newFromNumber( 555 ) );
		$someValueClaim = new Claim( new PropertySomeValueSnak( 44 ) );
		$newNoValueClaim = new Claim( new PropertyNoValueSnak( 43 ) );
		$oldNoValueClaim->setGuid( $entity->getId()->getPrefixedId() . '$D8404XXA-25E4-4004-AG93-A3290GGG9C0P' );
		
		$args = array();

		$args[] = array ( $entity, clone $noValueClaim , 'add' , array( clone $noValueClaim ) );
		$args[] = array ( $entity, clone $someValueClaim , 'add' , array( clone $noValueClaim, clone $someValueClaim ) );
		$args[] = array ( $entity, clone $noValueClaim , 'remove' , array( clone $someValueClaim ) );
		$args[] = array ( $entity, clone $someValueClaim , 'remove' , array( ) );
		$args[] = array ( $entity, clone $oldNoValueClaim , 'add' , array( clone $newNoValueClaim ) );
		$args[] = array ( $entity, clone $newNoValueClaim , 'remove' , array( ) );

		return $args;
	}

	/**
	 * @dataProvider changeOpClaimProvider
	 *
	 * @param Entity $entity
	 * @param $claim
	 * @param $action
	 * @param Claim[] $expectedClaims
	 * @internal param \Wikibase\ChangeOpClaim $changeOpClaim
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

}
