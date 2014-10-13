<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOpReferenceRemove;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\ChangeOp\ChangeOpReferenceRemove
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ChangeOpReferenceRemoveTest extends \PHPUnit_Framework_TestCase {

	public function invalidConstructorProvider() {
		$args = array();
		$args[] = array( '', '' );
		$args[] = array( '', 'foo' );
		$args[] = array( 'foo', '' );
		return $args;
	}

	/**
	 * @dataProvider invalidConstructorProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct( $claimGuid, $referenceHash ) {
		new ChangeOpReferenceRemove( $claimGuid, $referenceHash );
	}

	public function changeOpRemoveProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$args = array();

		$item = $this->newItemWithClaim( 'q345', $snak );
		$claims = $item->getClaims();
		/** @var Statement $claim */
		$claim = reset( $claims );
		$claimGuid = $claim->getGuid();
		$snaks = new SnakList();
		$snaks[] = new PropertyValueSnak( 78462378, new StringValue( 'newQualifier' ) );
		$newReference = new Reference( $snaks );
		$references = $claim->getReferences();
		$references->addReference( $newReference );
		$claim->setReferences( $references );
		$item->setClaims( new Claims( $claims ) );
		$referenceHash = $newReference->getHash();
		$changeOp = new ChangeOpReferenceRemove( $claimGuid, $referenceHash );
		$args[ 'Removing a single reference' ] = array ( $item, $changeOp, $referenceHash );

		$item = $this->newItemWithClaim( 'q346', $snak );
		$claims = $item->getClaims();
		/** @var Statement $claim */
		$claim = reset( $claims );
		$claimGuid = $claim->getGuid();
		$snaks = new SnakList();
		$snaks[] = new PropertyValueSnak( 78462378, new StringValue( 'newQualifier' ) );
		$newReference = new Reference( $snaks );
		$references = $claim->getReferences();
		$references->addReference( $newReference );
		$references->addReference( $newReference );
		$claim->setReferences( $references );
		$item->setClaims( new Claims( $claims ) );
		$referenceHash = $newReference->getHash();
		$changeOp = new ChangeOpReferenceRemove( $claimGuid, $referenceHash );
		$args[ 'Removing references that have the same hash' ] = array ( $item, $changeOp, $referenceHash );

		return $args;
	}

	/**
	 * @dataProvider changeOpRemoveProvider
	 *
	 * @param Entity $item
	 * @param ChangeOpReferenceRemove $changeOp
	 * @param string $referenceHash
	 */
	public function testApplyRemoveReference( $item, $changeOp, $referenceHash ) {
		$this->assertTrue( $changeOp->apply( $item ), "Applying the ChangeOp did not return true" );
		$claims = $item->getClaims();
		$this->assertCount( 1, $claims, 'More than one claim returned on item...' );
		/** @var Statement $claim */
		$claim = reset( $claims );
		$references = $claim->getReferences();
		$this->assertFalse( $references->hasReferenceHash( $referenceHash ), "Reference still exists" );
	}

	private function newItemWithClaim( $itemIdString, $snak ) {
		$item = Item::newEmpty();
		$item->setId( new ItemId( $itemIdString ) );

		$claim = $item->newClaim( $snak );
		$claim->setGuid( $item->getId()->getSerialization() . '$D8494TYA-25E4-4334-AG03-A3290BCT9CQP' );
		$claims = new Claims();
		$claims->addClaim( $claim );
		$item->setClaims( $claims );

		return $item;
	}

}
