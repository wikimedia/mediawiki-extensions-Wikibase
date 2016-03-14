<?php

namespace Wikibase\Test;

use DataValues\NumberValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\ChangeOpQualifier;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\ChangeOp\ChangeOpQualifier
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @license GPL-2.0+
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
		$item = new Item( new ItemId( 'Q42' ) );

		$guidGenerator = new GuidGenerator();
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
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$guid = $statement->getGuid();
		$newQualifier = new PropertyValueSnak( 78462378, new StringValue( 'newQualifier' ) );
		$changeOp = new ChangeOpQualifier( $guid, $newQualifier, '', $this->mockProvider->getMockSnakValidator() );
		$snakHash = $newQualifier->getHash();
		$args[] = array( $item, $changeOp, $snakHash );

		return $args;
	}

	/**
	 * @dataProvider changeOpAddProvider
	 */
	public function testApplyAddNewQualifier( Item $item, ChangeOpQualifier $changeOp, $snakHash ) {
		$changeOp->apply( $item );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$qualifiers = $statement->getQualifiers();
		$this->assertTrue( $qualifiers->hasSnakHash( $snakHash ), "No qualifier with expected hash" );
	}

	public function changeOpSetProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$args = array();

		$item = $this->newItemWithClaim( $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$guid = $statement->getGuid();
		$newQualifier = new PropertyValueSnak( 78462378, new StringValue( 'newQualifier' ) );
		$statement->getQualifiers()->addSnak( $newQualifier );
		$snakHash = $newQualifier->getHash();
		$changedQualifier = new PropertyValueSnak( 78462378, new StringValue( 'changedQualifier' ) );
		$changeOp = new ChangeOpQualifier( $guid, $changedQualifier, $snakHash, $this->mockProvider->getMockSnakValidator() );
		$args[] = array( $item, $changeOp, $changedQualifier->getHash() );

		return $args;
	}

	/**
	 * @dataProvider changeOpSetProvider
	 */
	public function testApplySetQualifier( Item $item, ChangeOpQualifier $changeOp, $snakHash ) {
		$changeOp->apply( $item );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$qualifiers = $statement->getQualifiers();
		$this->assertTrue( $qualifiers->hasSnakHash( $snakHash ), "No qualifier with expected hash" );
	}

	private function newItemWithClaim( $snak ) {
		$item = new Item( new ItemId( 'Q123' ) );

		$item->getStatements()->addNewStatement(
			$snak,
			null,
			null,
			$item->getId()->getSerialization() . '$D8404CDA-25E4-4334-AG03-A3290BCD9CQP'
		);

		return $item;
	}

	public function applyInvalidProvider() {
		$p11 = new PropertyId( 'P11' );
		$q17 = new ItemId( 'Q17' );

		$item = new Item( $q17 );
		$claimGuid = $this->mockProvider->getGuidGenerator()->newGuid( $q17 );
		$badGuid = $this->mockProvider->getGuidGenerator()->newGuid( $q17 );

		$oldSnak = new PropertyValueSnak( $p11, new StringValue( "old qualifier" ) );

		$snak = new PropertyNoValueSnak( $p11 );
		$qualifiers = new SnakList( array( $oldSnak ) );
		$item->getStatements()->addNewStatement( $snak, $qualifiers, null, $claimGuid );

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
	public function testApplyInvalid( EntityDocument $entity, $claimGuid, Snak $snak, $snakHash = '' ) {
		$this->setExpectedException( ChangeOpException::class );
		$changeOpQualifier = new ChangeOpQualifier(
			$claimGuid,
			$snak,
			$snakHash,
			$this->mockProvider->getMockSnakValidator()
		);

		$changeOpQualifier->apply( $entity );
	}

	public function validateProvider() {
		$p11 = new PropertyId( 'P11' );
		$q17 = new ItemId( 'Q17' );

		$item = new Item( $q17 );
		$claimGuid = $this->mockProvider->getGuidGenerator()->newGuid( $q17 );

		$oldSnak = new PropertyValueSnak( $p11, new StringValue( "old qualifier" ) );

		$snak = new PropertyNoValueSnak( $p11 );
		$qualifiers = new SnakList( array( $oldSnak ) );
		$item->getStatements()->addNewStatement( $snak, $qualifiers, null, $claimGuid );

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
	public function testValidate( EntityDocument $entity, $claimGuid, Snak $snak, $snakHash = '' ) {
		$changeOpQualifier = new ChangeOpQualifier(
			$claimGuid,
			$snak,
			$snakHash,
			$this->mockProvider->getMockSnakValidator()
		);

		$result = $changeOpQualifier->validate( $entity );
		$this->assertFalse( $result->isValid(), 'isValid()' );
	}

}
