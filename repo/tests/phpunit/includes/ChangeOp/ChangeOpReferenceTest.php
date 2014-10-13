<?php

namespace Wikibase\Test;

use DataValues\NumberValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOpReference;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\ClaimGuidGenerator;

/**
 * @covers Wikibase\ChangeOp\ChangeOpReference
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpReferenceTest extends \PHPUnit_Framework_TestCase {

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
		$snaks = new SnakList();
		$snaks[] = new PropertyValueSnak( 7201010, new StringValue( 'o_O' ) );
		$validReference = new Reference( $snaks );
		$validReferenceHash = $validReference->getHash();

		$args = array();
		$args[] = array( 123, $validReference, $validReferenceHash );
		$args[] = array( '', $validReference, $validReferenceHash );
		$args[] = array( $validClaimGuid, $validReference, 123 );
		$args[] = array( $validClaimGuid, $validReference, $validReferenceHash, 'string' );

		return $args;
	}

	/**
	 * @dataProvider invalidArgumentProvider
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct( $claimGuid, $reference, $referenceHash, $index = null ) {
		new ChangeOpReference( $claimGuid, $reference, $referenceHash, $this->mockProvider->getMockSnakValidator(), $index );
	}

	public function changeOpAddProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$args = array();

		$item = $this->newItemWithClaim( $snak );
		$claims = $item->getClaims();
		$claim = reset( $claims );
		$claimGuid = $claim->getGuid();
		$snaks = new SnakList();
		$snaks[] = new PropertyValueSnak( 78462378, new StringValue( 'newQualifier' ) );
		$newReference = new Reference( $snaks );
		$changeOp = new ChangeOpReference( $claimGuid, $newReference, '', $this->mockProvider->getMockSnakValidator() );
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
		$claims = $item->getClaims();
		/** @var Statement $claim */
		$claim = reset( $claims );
		$references = $claim->getReferences();
		$this->assertTrue( $references->hasReferenceHash( $referenceHash ), "No reference with expected hash" );
	}

	public function changeOpAddProviderWithIndex() {
		$snak = new PropertyNoValueSnak( 1 );
		$args = array();

		$item = $this->newItemWithClaim( $snak );
		$claims = $item->getClaims();
		/** @var Statement $claim */
		$claim = reset( $claims );

		$references = array(
			new Reference( new SnakList( array( new PropertyNoValueSnak( 1 ) ) ) ),
			new Reference( new SnakList( array( new PropertyNoValueSnak( 2 ) ) ) ),
		);

		$referenceList = $claim->getReferences();
		$referenceList->addReference( $references[0] );
		$referenceList->addReference( $references[1] );

		$item->setClaims( new Claims( $claims ) );

		$claimGuid = $claim->getGuid();

		$newReference = new Reference( new SnakList( array( new PropertyNoValueSnak( 3 ) ) ) );
		$newReferenceIndex = 1;

		$changeOp = new ChangeOpReference(
			$claimGuid,
			$newReference,
			'',
			$this->mockProvider->getMockSnakValidator(),
			$newReferenceIndex
		);

		$args[] = array ( $item, $changeOp, $newReference, $newReferenceIndex );

		return $args;
	}

	/**
	 * @dataProvider changeOpAddProviderWithIndex
	 *
	 * @param Entity $item
	 * @param ChangeOpReference $changeOp
	 * @param Reference $newReference
	 * @param int $expectedIndex
	 */
	public function testApplyAddNewReferenceWithIndex(
		$item,
		$changeOp,
		$newReference,
		$expectedIndex
	) {
		$this->assertTrue( $changeOp->apply( $item ), 'Applying the ChangeOp did not return true' );
		$claims = $item->getClaims();
		/** @var Statement $claim */
		$claim = reset( $claims );
		$references = $claim->getReferences();
		$this->assertEquals( $expectedIndex, $references->indexOf( $newReference ) );
	}

	public function changeOpSetProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$args = array();

		$item = $this->newItemWithClaim( $snak );
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
		$snaks = new SnakList();
		$snaks[] = new PropertyValueSnak( 78462378, new StringValue( 'changedQualifier' ) );
		$changedReference = new Reference( $snaks );
		$changeOp = new ChangeOpReference( $claimGuid, $changedReference, $referenceHash, $this->mockProvider->getMockSnakValidator() );
		$args[] = array ( $item, $changeOp, $changedReference->getHash() );

		// Just change a reference's index:
		$item = $this->newItemWithClaim( $snak );
		$claims = $item->getClaims();
		$claim = reset( $claims );

		/** @var Reference[] $references */
		$references = array(
			new Reference( new SnakList( array( new PropertyNoValueSnak( 1 ) ) ) ),
			new Reference( new SnakList( array( new PropertyNoValueSnak( 2 ) ) ) ),
		);

		$referenceList = $claim->getReferences();
		$referenceList->addReference( $references[0] );
		$referenceList->addReference( $references[1] );

		$claim->setReferences( $referenceList );
		$item->setClaims( new Claims( $claims ) );
		$changeOp = new ChangeOpReference(
			$claim->getGuid(),
			$references[1],
			$references[1]->getHash(),
			$this->mockProvider->getMockSnakValidator(),
			0
		);
		$args[] = array ( $item, $changeOp, $references[1]->getHash() );

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
		$claims = $item->getClaims();
		/** @var Statement $claim */
		$claim = reset( $claims );
		$references = $claim->getReferences();
		$this->assertTrue( $references->hasReferenceHash( $referenceHash ), "No reference with expected hash" );
	}

	private function newItemWithClaim( $snak ) {
		$item = Item::newEmpty();
		$item->setId( 123 );

		$claim = $item->newClaim( $snak );
		$claim->setGuid( $item->getId()->getSerialization() . '$D8494TYA-25E4-4334-AG03-A3290BCT9CQP' );
		$claims = new Claims();
		$claims->addClaim( $claim );
		$item->setClaims( $claims );

		return $item;
	}

	public function provideApplyInvalid() {
		$p11 = new PropertyId( 'P11' );
		$q17 = new ItemId( 'Q17' );

		$item = Item::newEmpty();
		$item->setId( $q17 );
		$claimGuid = $this->mockProvider->getGuidGenerator()->newGuid( $q17 );
		$badGuid = $this->mockProvider->getGuidGenerator()->newGuid( $q17 );

		$oldSnak = new PropertyValueSnak( $p11, new StringValue( "old reference" ) );
		$oldReference = new Reference( new SnakList( array( $oldSnak ) ) );

		$claim = new Statement( new Claim( new PropertyNoValueSnak( $p11 ), new SnakList( array( $oldSnak ) ) ) );
		$claim->setGuid( $claimGuid );
		$item->addClaim( $claim );

		//NOTE: the mock validator will consider the string "INVALID" to be invalid.
		$goodSnak = new PropertyValueSnak( $p11, new StringValue( 'good' ) );
		$badSnak = new PropertyValueSnak( $p11, new StringValue( 'INVALID' ) );

		$goodReference = new Reference( new SnakList( array( $goodSnak ) ) );

		$refHash = $oldReference->getHash();
		$badRefHash = sha1( 'baosdfhasdfj' );

		$cases = array();
		$cases['malformed claim guid'] = array( $item, 'NotAGuid', $goodReference, '' );
		$cases['unknown claim guid'] = array( $item, $badGuid, $goodReference, $refHash );
		$cases['unknown reference hash'] = array( $item, $claimGuid, $goodReference, $badRefHash );

		return $cases;
	}

	/**
	 * @dataProvider provideApplyInvalid
	 */
	public function testApplyInvalid( Entity $entity, $claimGuid, Reference $reference, $referenceHash = '', $index = null ) {
		$this->setExpectedException( 'Wikibase\ChangeOp\ChangeOpException' );

		$changeOpReference = new ChangeOpReference(
			$claimGuid,
			$reference,
			$referenceHash,
			$this->mockProvider->getMockSnakValidator(),
			$index
		);

		$entity = $entity->copy();
		$changeOpReference->apply( $entity );
	}

	public function provideValidate() {
		$p11 = new PropertyId( 'P11' );
		$q17 = new ItemId( 'Q17' );

		$item = Item::newEmpty();
		$item->setId( $q17 );
		$claimGuid = $this->mockProvider->getGuidGenerator()->newGuid( $q17 );

		$oldSnak = new PropertyValueSnak( $p11, new StringValue( "old reference" ) );
		$oldReference = new Reference( new SnakList( array( $oldSnak ) ) );

		$claim = new Statement( new Claim( new PropertyNoValueSnak( $p11 ), new SnakList( array( $oldSnak ) ) ) );
		$claim->setGuid( $claimGuid );
		$item->addClaim( $claim );

		//NOTE: the mock validator will consider the string "INVALID" to be invalid.
		$badSnak = new PropertyValueSnak( $p11, new StringValue( 'INVALID' ) );
		$brokenSnak = new PropertyValueSnak( $p11, new NumberValue( 23 ) );

		$badReference = new Reference( new SnakList( array( $badSnak ) ) );
		$brokenReference = new Reference( new SnakList( array( $brokenSnak ) ) );

		$refHash = $oldReference->getHash();

		$cases = array();
		$cases['invalid snak value'] = array( $item, $claimGuid, $badReference, '' );
		$cases['invalid snak value type'] = array( $item, $claimGuid, $brokenReference, $refHash );

		return $cases;
	}

	/**
	 * @dataProvider provideValidate
	 */
	public function testValidate( Entity $entity, $claimGuid, Reference $reference, $referenceHash = '', $index = null ) {
		$changeOpReference = new ChangeOpReference(
			$claimGuid,
			$reference,
			$referenceHash,
			$this->mockProvider->getMockSnakValidator(),
			$index
		);

		$entity = $entity->copy();
		$result = $changeOpReference->validate( $entity );
		$this->assertFalse( $result->isValid(), 'isValid()' );
	}

}
