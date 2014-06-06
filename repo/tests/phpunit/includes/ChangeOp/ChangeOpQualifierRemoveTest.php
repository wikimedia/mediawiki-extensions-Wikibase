<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOpQualifierRemove;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers Wikibase\ChangeOp\ChangeOpQualifierRemove
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ChangeOpQualifierRemoveTest extends \PHPUnit_Framework_TestCase {

	public function invalidConstructorProvider() {
		$args = array();
		$args[] = array( '', '' );
		$args[] = array( 'foo', '' );
		$args[] = array( '', 'foo' );
		return $args;
	}

	/**
	 * @dataProvider invalidConstructorProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct( $claimGuid, $snakHash ) {
		new ChangeOpQualifierRemove( $claimGuid, $snakHash );
	}

	public function changeOpRemoveProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$args = array();

		$item = $this->newItemWithClaim( 'q345', $snak );
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
		$changeOp = new ChangeOpQualifierRemove( $claimGuid, $snakHash );
		$args[] = array ( $item, $changeOp, $snakHash );

		return $args;
	}

	/**
	 * @dataProvider changeOpRemoveProvider
	 *
	 * @param Entity $item
	 * @param ChangeOpQualifierRemove $changeOp
	 * @param string $snakHash
	 */
	public function testApplyRemoveQualifier( $item, $changeOp, $snakHash ) {
		$this->assertTrue( $changeOp->apply( $item ), "Applying the ChangeOp did not return true" );
		$claims = $item->getClaims();
		/** @var Claim $claim */
		$claim = reset( $claims );
		$qualifiers = $claim->getQualifiers();
		$this->assertFalse( $qualifiers->hasSnakHash( $snakHash ), "Qualifier still exists" );
	}

	private function newItemWithClaim( $itemIdString, $snak ) {
		$item = Item::newEmpty();
		$item->setId( new ItemId( $itemIdString ) );

		$claim = $item->newClaim( $snak );
		$claim->setGuid( $itemIdString . '$D8404CDA-25E4-4334-AG03-A3290BCD9CQP' );
		$claims = new Claims();
		$claims->addClaim( $claim );
		$item->setClaims( $claims );

		return $item;
	}

} 