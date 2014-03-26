<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\ChangeOp\ChangeOpQualifier;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\ItemContent;
use InvalidArgumentException;
use Wikibase\Lib\ClaimGuidGenerator;

/**
 * @covers Wikibase\ChangeOp\ChangeOpQualifier
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
		$guidGenerator = new ClaimGuidGenerator();
		$validClaimGuid = $guidGenerator->newGuid( $item->getId() );
		$validSnak = new PropertyValueSnak( 7201010, new StringValue( 'o_O' ) );
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
		new ChangeOpQualifier( $claimGuid, $snak, $snakHash );
	}

	public function changeOpAddProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$args = array();

		$item = $this->provideNewItemWithClaim( 'q123', $snak );
		$claims = $item->getClaims();
		$claim = reset( $claims );
		$claimGuid = $claim->getGuid();
		$newQualifier = new PropertyValueSnak( 78462378, new StringValue( 'newQualifier' ) );
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
		/** @var Claim $claim */
		$claim = reset( $claims );
		$qualifiers = $claim->getQualifiers();
		$this->assertTrue( $qualifiers->hasSnakHash( $snakHash ), "No qualifier with expected hash" );
	}

	public function changeOpSetProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$args = array();

		$item = $this->provideNewItemWithClaim( 'q123', $snak );
		$claims = $item->getClaims();
		/** @var Claim $claim */
		$claim = reset( $claims );
		$claimGuid = $claim->getGuid();
		$newQualifier = new PropertyValueSnak( 78462378, new StringValue( 'newQualifier' ) );
		$qualifiers = $claim->getQualifiers();
		$qualifiers->addSnak( $newQualifier );
		$claim->setQualifiers( $qualifiers );
		$item->setClaims( new Claims( $claims ) );
		$snakHash = $newQualifier->getHash();
		$changedQualifier = new PropertyValueSnak( 78462378, new StringValue( 'changedQualifier' ) );
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
		/** @var Claim $claim */
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
