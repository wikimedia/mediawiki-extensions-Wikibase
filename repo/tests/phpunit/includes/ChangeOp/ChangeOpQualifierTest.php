<?php

namespace Wikibase\Test;

use Wikibase\Claim;
use Wikibase\Claims;
use Wikibase\ChangeOp\ChangeOpQualifier;
use Wikibase\Entity;
use Wikibase\ItemContent;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Lib\ClaimGuidGenerator;
use InvalidArgumentException;

/**
 * @covers Wikibase\ChangeOp\ChangeOpQualifier
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
class ChangeOpQualifierTest extends \PHPUnit_Framework_TestCase {

	public function invalidArgumentProvider() {
		$item = ItemContent::newFromArray( array( 'entity' => 'q42' ) )->getEntity();
		$guidGenerator = new \Wikibase\Lib\ClaimGuidGenerator( $item->getId() );
		$validClaimGuid = $guidGenerator->newGuid();
		$validSnak = new \Wikibase\PropertyValueSnak( 7201010, new \DataValues\StringValue( 'o_O' ) );
		$validSnakHash = $validSnak->getHash();

		$args = array();
		$args[] = array( 123, $validSnak, $validSnakHash );
		$args[] = array( '', $validSnak, $validSnakHash );
		$args[] = array( 123, null, $validSnakHash );
		$args[] = array( $validClaimGuid, 'notASnak', $validSnakHash );
		$args[] = array( $validClaimGuid, 'notASnak', '' );
		$args[] = array( $validClaimGuid, null, '' );
		$args[] = array( $validClaimGuid, $validSnak, 123 );

		return $args;
	}

	/**
	 * @dataProvider invalidArgumentProvider
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct( $claimGuid, $snak, $snakHash ) {
		$ChangeOpQualifier = new ChangeOpQualifier( $claimGuid, $snak, $snakHash );
	}

	public function changeOpAddProvider() {
		$snak = new \Wikibase\PropertyValueSnak( 2754236, new \DataValues\StringValue( 'test' ) );
		$args = array();

		$item = $this->provideNewItemWithClaim( 'q123', $snak );
		$claims = $item->getClaims();
		$claim = reset( $claims );
		$claimGuid = $claim->getGuid();
		$newQualifier = new \Wikibase\PropertyValueSnak( 78462378, new \DataValues\StringValue( 'newQualifier' ) );
		$changeOp = new ChangeOpQualifier( $claimGuid, $newQualifier, '' );
		$snakHash = $newQualifier->getHash();
		$args[] = array ( $item, $changeOp, $snakHash );

		return $args;
	}

	/**
	 * @dataProvider changeOpAddProvider
	 *
	 * @param Entity $item
	 * @param ChangeOpQualifier $changeOp
	 * @param string $snakHash
	 */
	public function testApplyAddNewQualifier( $item, $changeOp, $snakHash ) {
		$this->assertTrue( $changeOp->apply( $item ), "Applying the ChangeOp did not return true" );
		$claims = new Claims( $item->getClaims() );
		$claim = reset( $claims );
		$qualifiers = $claim->getQualifiers();
		$this->assertTrue( $qualifiers->hasSnakHash( $snakHash ), "No qualifier with expected hash" );
	}

	public function changeOpRemoveProvider() {
		$snak = new \Wikibase\PropertyValueSnak( 2754236, new \DataValues\StringValue( 'test' ) );
		$args = array();

		$item = $this->provideNewItemWithClaim( 'q345', $snak );
		$claims = $item->getClaims();
		$claim = reset( $claims );
		$claimGuid = $claim->getGuid();
		$newQualifier = new \Wikibase\PropertyValueSnak( 78462378, new \DataValues\StringValue( 'newQualifier' ) );
		$qualifiers = $claim->getQualifiers();
		$qualifiers->addSnak( $newQualifier );
		$claim->setQualifiers( $qualifiers );
		$item->setClaims( new Claims( $claims ) );
		$snakHash = $newQualifier->getHash();
		$changeOp = new ChangeOpQualifier( $claimGuid, null, $snakHash );
		$args[] = array ( $item, $changeOp, $snakHash );

		return $args;
	}

	/**
	 * @dataProvider changeOpRemoveProvider
	 *
	 * @param Entity $item
	 * @param ChangeOpQualifier $changeOp
	 * @param string $snakHash
	 */
	public function testApplyRemoveQualifier( $item, $changeOp, $snakHash ) {
		$this->assertTrue( $changeOp->apply( $item ), "Applying the ChangeOp did not return true" );
		$claims = new Claims( $item->getClaims() );
		$claim = reset( $claims );
		$qualifiers = $claim->getQualifiers();
		$this->assertFalse( $qualifiers->hasSnakHash( $snakHash ), "Qualifier still exists" );
	}

	public function changeOpSetProvider() {
		$snak = new \Wikibase\PropertyValueSnak( 2754236, new \DataValues\StringValue( 'test' ) );
		$args = array();

		$item = $this->provideNewItemWithClaim( 'q123', $snak );
		$claims = $item->getClaims();
		$claim = reset( $claims );
		$claimGuid = $claim->getGuid();
		$newQualifier = new \Wikibase\PropertyValueSnak( 78462378, new \DataValues\StringValue( 'newQualifier' ) );
		$qualifiers = $claim->getQualifiers();
		$qualifiers->addSnak( $newQualifier );
		$claim->setQualifiers( $qualifiers );
		$item->setClaims( new Claims( $claims ) );
		$snakHash = $newQualifier->getHash();
		$changedQualifier = new \Wikibase\PropertyValueSnak( 78462378, new \DataValues\StringValue( 'changedQualifier' ) );
		$changeOp = new ChangeOpQualifier( $claimGuid, $changedQualifier, $snakHash );
		$args[] = array ( $item, $changeOp, $changedQualifier->getHash() );

		return $args;
	}

	/**
	 * @dataProvider changeOpSetProvider
	 *
	 * @param Entity $item
	 * @param ChangeOpQualifier $changeOp
	 * @param string $snakHash
	 */
	public function testApplySetQualifier( $item, $changeOp, $snakHash ) {
		$this->assertTrue( $changeOp->apply( $item ), "Applying the ChangeOp did not return true" );
		$claims = new Claims( $item->getClaims() );
		$claim = reset( $claims );
		$qualifiers = $claim->getQualifiers();
		$this->assertTrue( $qualifiers->hasSnakHash( $snakHash ), "No qualifier with expected hash" );
	}

	protected function provideNewItemWithClaim( $itemId, $snak ) {
		$entity = ItemContent::newFromArray( array( 'entity' => $itemId ) )->getEntity();
		$claim = $entity->newClaim( $snak );
		$claim->setGuid( $entity->getId()->getPrefixedId() . '$D8404CDA-25E4-4334-AG03-A3290BCD9CQP' );
		$claims = new Claims();
		$claims->addClaim( $claim );
		$entity->setClaims( $claims );
		return $entity;
	}
}
