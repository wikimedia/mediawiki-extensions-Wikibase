<?php

namespace Wikibase\Test;

use Wikibase\Claims;
use Wikibase\ChangeOp\ChangeOpReference;
use Wikibase\Entity;
use Wikibase\ItemContent;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Reference;
use Wikibase\SnakList;
use InvalidArgumentException;

/**
 * @covers Wikibase\ChangeOp\ChangeOpReference
 *
 * @since 0.4
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpReferenceTest extends \PHPUnit_Framework_TestCase {

	public function invalidArgumentProvider() {
		$item = ItemContent::newFromArray( array( 'entity' => 'q42' ) )->getEntity();
		$guidGenerator = new \Wikibase\Lib\ClaimGuidGenerator( $item->getId() );
		$validClaimGuid = $guidGenerator->newGuid();
		$snaks = new SnakList();
		$snaks[] = new \Wikibase\PropertyValueSnak( 7201010, new \DataValues\StringValue( 'o_O' ) );
		$validReference = new Reference( $snaks );
		$validReferenceHash = $validReference->getHash();

		$args = array();
		$args[] = array( 123, $validReference, $validReferenceHash );
		$args[] = array( '', $validReference, $validReferenceHash );
		$args[] = array( '', null, $validReferenceHash );
		$args[] = array( $validClaimGuid, $validReference, 123 );
		$args[] = array( $validClaimGuid, 'notAReference', $validReferenceHash );
		$args[] = array( $validClaimGuid, 'notAReference', '' );
		$args[] = array( $validClaimGuid, null, '' );

		return $args;
	}

	/**
	 * @dataProvider invalidArgumentProvider
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct( $claimGuid, $reference, $referenceHash ) {
		$ChangeOpQualifier = new ChangeOpReference( $claimGuid, $reference, $referenceHash );
	}

	public function changeOpAddProvider() {
		$snak = new \Wikibase\PropertyValueSnak( 2754236, new \DataValues\StringValue( 'test' ) );
		$args = array();

		$item = $this->provideNewItemWithClaim( 'q123', $snak );
		$claims = $item->getClaims();
		$claimGuid = $claims[0]->getGuid();
		$snaks = new SnakList();
		$snaks[] = new \Wikibase\PropertyValueSnak( 78462378, new \DataValues\StringValue( 'newQualifier' ) );
		$newReference = new Reference( $snaks );
		$changeOp = new ChangeOpReference( $claimGuid, $newReference, '' );
		$referenceHash = $newReference->getHash();
		$args[] = array ( $item, $changeOp, $referenceHash );

		return $args;
	}

	/**
	 * @dataProvider changeOpAddProvider
	 *
	 * @param Entity $item
	 * @param ChangeOpReference $changeOp
	 * @param string $referenceHash
	 */
	public function testApplyAddNewReference( $item, $changeOp, $referenceHash ) {
		$this->assertTrue( $changeOp->apply( $item ), "Applying the ChangeOp did not return true" );
		$claims = new Claims( $item->getClaims() );
		$references = $claims[0]->getReferences();
		$this->assertTrue( $references->hasReferenceHash( $referenceHash ), "No reference with expected hash" );
	}

	public function changeOpRemoveProvider() {
		$snak = new \Wikibase\PropertyValueSnak( 2754236, new \DataValues\StringValue( 'test' ) );
		$args = array();

		$item = $this->provideNewItemWithClaim( 'q345', $snak );
		$claims = $item->getClaims();
		$claimGuid = $claims[0]->getGuid();
		$snaks = new SnakList();
		$snaks[] = new \Wikibase\PropertyValueSnak( 78462378, new \DataValues\StringValue( 'newQualifier' ) );
		$newReference = new Reference( $snaks );
		$references = $claims[0]->getReferences();
		$references->addReference( $newReference );
		$claims[0]->setReferences( $references );
		$item->setClaims( new Claims( $claims ) );
		$referenceHash = $newReference->getHash();
		$changeOp = new ChangeOpReference( $claimGuid, null, $referenceHash );
		$args[] = array ( $item, $changeOp, $referenceHash );

		return $args;
	}

	/**
	 * @dataProvider changeOpRemoveProvider
	 *
	 * @param Entity $item
	 * @param ChangeOpReference $changeOp
	 * @param string $referenceHash
	 */
	public function testApplyRemoveReference( $item, $changeOp, $referenceHash ) {
		$this->assertTrue( $changeOp->apply( $item ), "Applying the ChangeOp did not return true" );
		$claims = new Claims( $item->getClaims() );
		$references = $claims[0]->getReferences();
		$this->assertFalse( $references->hasReferenceHash( $referenceHash ), "Reference still exists" );
	}

	public function changeOpSetProvider() {
		$snak = new \Wikibase\PropertyValueSnak( 2754236, new \DataValues\StringValue( 'test' ) );
		$args = array();

		$item = $this->provideNewItemWithClaim( 'q123', $snak );
		$claims = $item->getClaims();
		$claimGuid = $claims[0]->getGuid();
		$snaks = new SnakList();
		$snaks[] = new \Wikibase\PropertyValueSnak( 78462378, new \DataValues\StringValue( 'newQualifier' ) );
		$newReference = new Reference( $snaks );
		$references = $claims[0]->getReferences();
		$references->addReference( $newReference );
		$claims[0]->setReferences( $references );
		$item->setClaims( new Claims( $claims ) );
		$referenceHash = $newReference->getHash();
		$snaks = new SnakList();
		$snaks[] = new \Wikibase\PropertyValueSnak( 78462378, new \DataValues\StringValue( 'changedQualifier' ) );
		$changedReference = new Reference( $snaks );
		$changeOp = new ChangeOpReference( $claimGuid, $changedReference, $referenceHash );
		$args[] = array ( $item, $changeOp, $changedReference->getHash() );

		return $args;
	}

	/**
	 * @dataProvider changeOpSetProvider
	 *
	 * @param Entity $item
	 * @param ChangeOpReference $changeOp
	 * @param string $referenceHash
	 */
	public function testApplySetReference( $item, $changeOp, $referenceHash ) {
		$this->assertTrue( $changeOp->apply( $item ), "Applying the ChangeOp did not return true" );
		$claims = new Claims( $item->getClaims() );
		$references = $claims[0]->getReferences();
		$this->assertTrue( $references->hasReferenceHash( $referenceHash ), "No reference with expected hash" );
	}

	protected function provideNewItemWithClaim( $itemId, $snak ) {
		$entity = ItemContent::newFromArray( array( 'entity' => $itemId ) )->getEntity();
		$claim = $entity->newClaim( $snak );
		$claim->setGuid( $entity->getId()->getPrefixedId() . '$D8494TYA-25E4-4334-AG03-A3290BCT9CQP' );
		$claims = new Claims();
		$claims->addClaim( $claim );
		$entity->setClaims( $claims );

		return $entity;
	}
}
