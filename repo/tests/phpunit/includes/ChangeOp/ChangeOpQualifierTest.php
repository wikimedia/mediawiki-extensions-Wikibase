<?php

namespace Wikibase\Test;

use DataValues\NumberValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOpQualifier;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
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
 * @author Daniel Kinzler
 */
class ChangeOpQualifierTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var ChangeOpTestMockProvider
	 */
	private $mockProvider;

	/**
	 * @param string|null $name
	 * @param array $data
	 * @param string $dataName
	 */
	public function __construct( $name = null, array $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->mockProvider = new ChangeOpTestMockProvider( $this );
	}

	public function invalidArgumentProvider() {
		$item = Item::newEmpty();
		$item->setId( 42 );

		$guidGenerator = new ClaimGuidGenerator();
		$validClaimGuid = $guidGenerator->newGuid( $item->getId() );
		$validSnak = new PropertyValueSnak( 7201010, new StringValue( 'o_O' ) );
		$validSnakHash = $validSnak->getHash();

		$args = array();
		$args[] = array( 123, $validSnak, $validSnakHash );
		$args[] = array( '', $validSnak, $validSnakHash );
		$args[] = array( $validClaimGuid, $validSnak, 123 );

		return $args;
	}

	/**
	 * @dataProvider invalidArgumentProvider
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct( $claimGuid, $snak, $snakHash ) {
		new ChangeOpQualifier( $claimGuid, $snak, $snakHash, $this->mockProvider->getMockSnakValidator() );
	}

	public function changeOpAddProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$args = array();

		$item = $this->newItemWithClaim( $snak );
		$claims = $item->getClaims();
		$claim = reset( $claims );
		$claimGuid = $claim->getGuid();
		$newQualifier = new PropertyValueSnak( 78462378, new StringValue( 'newQualifier' ) );
		$changeOp = new ChangeOpQualifier( $claimGuid, $newQualifier, '', $this->mockProvider->getMockSnakValidator() );
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
		$claims = $item->getClaims();
		/** @var Claim $claim */
		$claim = reset( $claims );
		$qualifiers = $claim->getQualifiers();
		$this->assertTrue( $qualifiers->hasSnakHash( $snakHash ), "No qualifier with expected hash" );
	}

	public function changeOpSetProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$args = array();

		$item = $this->newItemWithClaim( $snak );
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
		$changeOp = new ChangeOpQualifier( $claimGuid, $changedQualifier, $snakHash, $this->mockProvider->getMockSnakValidator() );
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
		$claims = $item->getClaims();
		/** @var Claim $claim */
		$claim = reset( $claims );
		$qualifiers = $claim->getQualifiers();
		$this->assertTrue( $qualifiers->hasSnakHash( $snakHash ), "No qualifier with expected hash" );
	}

	private function newItemWithClaim( $snak ) {
		$item = Item::newEmpty();
		$item->setId( 123 );

		$claim = $item->newClaim( $snak );
		$claim->setGuid( $item->getId()->getPrefixedId() . '$D8404CDA-25E4-4334-AG03-A3290BCD9CQP' );
		$claims = new Claims();
		$claims->addClaim( $claim );
		$item->setClaims( $claims );

		return $item;
	}

	public function applyInvalidProvider() {
		$p11 = new PropertyId( 'P11' );
		$q17 = new ItemId( 'Q17' );

		$item = Item::newEmpty();
		$item->setId( $q17 );
		$claimGuid = $this->mockProvider->getGuidGenerator()->newGuid( $q17 );
		$badGuid = $this->mockProvider->getGuidGenerator()->newGuid( $q17 );

		$oldSnak = new PropertyValueSnak( $p11, new StringValue( "old qualifier" ) );

		$claim = new Statement( new PropertyNoValueSnak( $p11 ), new SnakList( array( $oldSnak ) ) );
		$claim->setGuid( $claimGuid );
		$item->addClaim( $claim );

		//NOTE: the mock validator will consider the string "INVALID" to be invalid.
		$goodSnak = new PropertyValueSnak( $p11, new StringValue( 'good' ) );

		$snakHash = $oldSnak->getHash();
		$badSnakHash = sha1( "dummy" );

		$cases = array();
		$cases['malformed claim guid'] = array( $item, 'NotAGuid', $goodSnak, '' );
		$cases['unknown claim guid'] = array( $item, $badGuid, $goodSnak, $snakHash );
		$cases['unknown snak hash'] = array( $item, $claimGuid, $goodSnak, $badSnakHash );

		return $cases;
	}

	/**
	 * @dataProvider applyInvalidProvider
	 */
	public function testApplyInvalid( Entity $entity, $claimGuid, Snak $snak, $snakHash = '' ) {
		$this->setExpectedException( 'Wikibase\ChangeOp\ChangeOpException' );
		$changeOpQualifier = new ChangeOpQualifier(
			$claimGuid,
			$snak,
			$snakHash,
			$this->mockProvider->getMockSnakValidator()
		);

		$entity = $entity->copy();
		$changeOpQualifier->apply( $entity );
	}

	public function validateProvider() {
		$p11 = new PropertyId( 'P11' );
		$q17 = new ItemId( 'Q17' );

		$item = Item::newEmpty();
		$item->setId( $q17 );
		$claimGuid = $this->mockProvider->getGuidGenerator()->newGuid( $q17 );

		$oldSnak = new PropertyValueSnak( $p11, new StringValue( "old qualifier" ) );

		$claim = new Statement( new PropertyNoValueSnak( $p11 ), new SnakList( array( $oldSnak ) ) );
		$claim->setGuid( $claimGuid );
		$item->addClaim( $claim );

		//NOTE: the mock validator will consider the string "INVALID" to be invalid.
		$badSnak = new PropertyValueSnak( $p11, new StringValue( 'INVALID' ) );
		$brokenSnak = new PropertyValueSnak( $p11, new NumberValue( 23 ) );

		$snakHash = $oldSnak->getHash();

		$cases = array();
		$cases['invalid snak value'] = array( $item, $claimGuid, $badSnak, '' );
		$cases['invalid snak value type'] = array( $item, $claimGuid, $brokenSnak, $snakHash );

		return $cases;
	}

	/**
	 * @dataProvider validateProvider
	 */
	public function testValidate( Entity $entity, $claimGuid, Snak $snak, $snakHash = '' ) {
		$changeOpQualifier = new ChangeOpQualifier(
			$claimGuid,
			$snak,
			$snakHash,
			$this->mockProvider->getMockSnakValidator()
		);

		$entity = $entity->copy();
		$result = $changeOpQualifier->validate( $entity );
		$this->assertFalse( $result->isValid(), 'isValid()' );
	}

}
