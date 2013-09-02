<?php

namespace Wikibase\Test;

use Wikibase\Claim;
use Wikibase\Claims;
use Wikibase\ChangeOpQualifier;
use Wikibase\Entity;
use Wikibase\ItemContent;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Lib\ClaimGuidGenerator;
use InvalidArgumentException;

/**
 * @covers Wikibase\ChangeOpQualifier
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
		$validIdFormatter = WikibaseRepo::getDefaultInstance()->getIdFormatter();
		$guidGenerator = new \Wikibase\Lib\ClaimGuidGenerator( $item->getId() );
		$validClaimGuid = $guidGenerator->newGuid();
		$validSnak = new \Wikibase\PropertyValueSnak( 7201010, new \DataValues\StringValue( 'o_O' ) );
		$validSnakHash = $validSnak->getHash();

		$args = array();
		$args[] = array( 123, $validSnak, $validSnakHash, $validIdFormatter );
		$args[] = array( '', $validSnak, $validSnakHash, $validIdFormatter );
		$args[] = array( 123, null, $validSnakHash, $validIdFormatter );
		$args[] = array( $validClaimGuid, 'notASnak', $validSnakHash, $validIdFormatter );
		$args[] = array( $validClaimGuid, 'notASnak', '', $validIdFormatter );
		$args[] = array( $validClaimGuid, null, '', $validIdFormatter );
		$args[] = array( $validClaimGuid, $validSnak, 123, $validIdFormatter );

		return $args;
	}

	/**
	 * @dataProvider invalidArgumentProvider
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct( $claimGuid, $snak, $snakHash, $idFormatter ) {
		$ChangeOpQualifier = new ChangeOpQualifier( $claimGuid, $snak, $snakHash, $idFormatter );
	}

	public function changeOpAddProvider() {
		$idFormatter = WikibaseRepo::getDefaultInstance()->getIdFormatter();
		$snak = new \Wikibase\PropertyValueSnak( 2754236, new \DataValues\StringValue( 'test' ) );
		$args = array();

		$item = $this->provideNewItemWithClaim( 'q123', $snak );
		$claims = $item->getClaims();
		$claimGuid = $claims[0]->getGuid();
		$newQualifier = new \Wikibase\PropertyValueSnak( 78462378, new \DataValues\StringValue( 'newQualifier' ) );
		$changeOp = new ChangeOpQualifier( $claimGuid, $newQualifier, '', $idFormatter );
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
		$qualifiers = $claims[0]->getQualifiers();
		$this->assertTrue( $qualifiers->hasSnakHash( $snakHash ), "No qualifier with expected hash" );
	}

	public function changeOpRemoveProvider() {
		$idFormatter = WikibaseRepo::getDefaultInstance()->getIdFormatter();
		$snak = new \Wikibase\PropertyValueSnak( 2754236, new \DataValues\StringValue( 'test' ) );
		$args = array();

		$item = $this->provideNewItemWithClaim( 'q345', $snak );
		$claims = $item->getClaims();
		$claimGuid = $claims[0]->getGuid();
		$newQualifier = new \Wikibase\PropertyValueSnak( 78462378, new \DataValues\StringValue( 'newQualifier' ) );
		$qualifiers = $claims[0]->getQualifiers();
		$qualifiers->addSnak( $newQualifier );
		$claims[0]->setQualifiers( $qualifiers );
		$item->setClaims( new Claims( $claims ) );
		$snakHash = $newQualifier->getHash();
		$changeOp = new ChangeOpQualifier( $claimGuid, null, $snakHash, $idFormatter );
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
		$qualifiers = $claims[0]->getQualifiers();
		$this->assertFalse( $qualifiers->hasSnakHash( $snakHash ), "Qualifier still exists" );
	}

	public function changeOpSetProvider() {
		$idFormatter = WikibaseRepo::getDefaultInstance()->getIdFormatter();
		$snak = new \Wikibase\PropertyValueSnak( 2754236, new \DataValues\StringValue( 'test' ) );
		$args = array();

		$item = $this->provideNewItemWithClaim( 'q123', $snak );
		$claims = $item->getClaims();
		$claimGuid = $claims[0]->getGuid();
		$newQualifier = new \Wikibase\PropertyValueSnak( 78462378, new \DataValues\StringValue( 'newQualifier' ) );
		$qualifiers = $claims[0]->getQualifiers();
		$qualifiers->addSnak( $newQualifier );
		$claims[0]->setQualifiers( $qualifiers );
		$item->setClaims( new Claims( $claims ) );
		$snakHash = $newQualifier->getHash();
		$changedQualifier = new \Wikibase\PropertyValueSnak( 78462378, new \DataValues\StringValue( 'changedQualifier' ) );
		$changeOp = new ChangeOpQualifier( $claimGuid, $changedQualifier, $snakHash, $idFormatter );
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
		$qualifiers = $claims[0]->getQualifiers();
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
