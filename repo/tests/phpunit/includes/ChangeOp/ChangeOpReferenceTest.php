<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use DataValues\NumberValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\ChangeOpReference;
use Wikibase\Repo\Store\EntityPermissionChecker;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangeOpReference
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpReferenceTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var ChangeOpTestMockProvider
	 */
	private $mockProvider;

	/**
	 * @param string|null $name
	 * @param array $data
	 * @param string $dataName
	 */
	public function __construct( $name = null, array $data = [], $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->mockProvider = new ChangeOpTestMockProvider( $this );
	}

	public function invalidArgumentProvider() {
		$item = new Item( new ItemId( 'Q42' ) );

		$guidGenerator = new GuidGenerator();
		$guid = $guidGenerator->newGuid( $item->getId() );
		$snaks = new SnakList();
		$snaks[] = new PropertyValueSnak( 7201010, new StringValue( 'o_O' ) );
		$validReference = new Reference( $snaks );
		$validReferenceHash = $validReference->getHash();

		return [
			[ 123, $validReference, $validReferenceHash ],
			[ '', $validReference, $validReferenceHash ],
			[ $guid, $validReference, 123 ],
			[ $guid, $validReference, $validReferenceHash, 'string' ],
		];
	}

	/**
	 * @dataProvider invalidArgumentProvider
	 */
	public function testInvalidConstruct(
		$guid,
		Reference $reference,
		$referenceHash,
		$index = null
	) {
		$this->expectException( InvalidArgumentException::class );
		new ChangeOpReference(
			$guid,
			$reference,
			$referenceHash,
			$this->mockProvider->getMockSnakValidator(),
			$index
		);
	}

	public function changeOpAddProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );

		$item = $this->newItem( $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$snaks = new SnakList();
		$snaks[] = new PropertyValueSnak( 78462378, new StringValue( 'newQualifier' ) );
		$newReference = new Reference( $snaks );
		$changeOp = new ChangeOpReference(
			$statement->getGuid(),
			$newReference,
			'',
			$this->mockProvider->getMockSnakValidator()
		);
		$referenceHash = $newReference->getHash();

		return [
			[ $item, $changeOp, $referenceHash ],
		];
	}

	/**
	 * @dataProvider changeOpAddProvider
	 */
	public function testApplyAddNewReference(
		Item $item,
		ChangeOpReference $changeOp,
		$referenceHash
	) {
		$changeOpResult = $changeOp->apply( $item );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$references = $statement->getReferences();
		$this->assertTrue( $references->hasReferenceHash( $referenceHash ), 'Reference not found' );
		$this->assertTrue( $changeOpResult->isEntityChanged() );
	}

	public function changeOpAddProviderWithIndex() {
		$snak = new PropertyNoValueSnak( 1 );
		$args = [];

		$item = $this->newItem( $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );

		$references = [
			new Reference( new SnakList( [ new PropertyNoValueSnak( 1 ) ] ) ),
			new Reference( new SnakList( [ new PropertyNoValueSnak( 2 ) ] ) ),
		];

		$referenceList = $statement->getReferences();
		$referenceList->addReference( $references[0] );
		$referenceList->addReference( $references[1] );

		$newReference = new Reference( new SnakList( [ new PropertyNoValueSnak( 3 ) ] ) );
		$newReferenceIndex = 1;

		$changeOp = new ChangeOpReference(
			$statement->getGuid(),
			$newReference,
			'',
			$this->mockProvider->getMockSnakValidator(),
			$newReferenceIndex
		);

		$args[] = [ $item, $changeOp, $newReference, $newReferenceIndex ];

		return $args;
	}

	/**
	 * @dataProvider changeOpAddProviderWithIndex
	 */
	public function testApplyAddNewReferenceWithIndex(
		Item $item,
		ChangeOpReference $changeOp,
		Reference $newReference,
		$expectedIndex
	) {
		$changeOpResult = $changeOp->apply( $item );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$references = $statement->getReferences();
		$this->assertEquals( $expectedIndex, $references->indexOf( $newReference ) );
		$this->assertTrue( $changeOpResult->isEntityChanged() );
	}

	public function changeOpSetProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$args = [];

		$item = $this->newItem( $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$snaks = new SnakList();
		$snaks[] = new PropertyValueSnak( 78462378, new StringValue( 'newQualifier' ) );
		$newReference = new Reference( $snaks );
		$statement->getReferences()->addReference( $newReference );
		$referenceHash = $newReference->getHash();
		$snaks = new SnakList();
		$snaks[] = new PropertyValueSnak( 78462378, new StringValue( 'changedQualifier' ) );
		$changedReference = new Reference( $snaks );
		$changeOp = new ChangeOpReference(
			$statement->getGuid(),
			$changedReference,
			$referenceHash,
			$this->mockProvider->getMockSnakValidator()
		);
		$args[] = [ $item, $changeOp, $changedReference->getHash() ];

		// Just change a reference's index:
		$item = $this->newItem( $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );

		/** @var Reference[] $references */
		$references = [
			new Reference( new SnakList( [ new PropertyNoValueSnak( 1 ) ] ) ),
			new Reference( new SnakList( [ new PropertyNoValueSnak( 2 ) ] ) ),
		];

		$referenceList = $statement->getReferences();
		$referenceList->addReference( $references[0] );
		$referenceList->addReference( $references[1] );

		$changeOp = new ChangeOpReference(
			$statement->getGuid(),
			$references[1],
			$references[1]->getHash(),
			$this->mockProvider->getMockSnakValidator(),
			0
		);
		$args[] = [ $item, $changeOp, $references[1]->getHash() ];

		return $args;
	}

	/**
	 * @dataProvider changeOpSetProvider
	 */
	public function testApplySetReference(
		Item $item,
		ChangeOpReference $changeOp,
		$referenceHash
	) {
		$changeOpResult = $changeOp->apply( $item );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$references = $statement->getReferences();
		$this->assertTrue( $references->hasReferenceHash( $referenceHash ), 'Reference not found' );
		$this->assertTrue( $changeOpResult->isEntityChanged() );
	}

	/**
	 * @param Snak $snak
	 *
	 * @return Item
	 */
	private function newItem( Snak $snak ) {
		$item = new Item( new ItemId( 'Q123' ) );

		$item->getStatements()->addNewStatement(
			$snak,
			null,
			null,
			$item->getId()->getSerialization() . '$D8494TYA-25E4-4334-AG03-A3290BCT9CQP'
		);

		return $item;
	}

	public function provideApplyInvalid() {
		$p11 = new NumericPropertyId( 'P11' );
		$q17 = new ItemId( 'Q17' );

		$item = new Item( $q17 );
		$goodGuid = 'GUID';
		$badGuid = 'UNKNOWN-GUID';

		$oldSnak = new PropertyValueSnak( $p11, new StringValue( "old reference" ) );

		$snak = new PropertyNoValueSnak( $p11 );
		$qualifiers = new SnakList( [ $oldSnak ] );
		$item->getStatements()->addNewStatement( $snak, $qualifiers, null, $goodGuid );

		$goodSnak = new PropertyValueSnak( $p11, new StringValue( 'good' ) );

		$goodReference = new Reference( new SnakList( [ $goodSnak ] ) );

		$badRefHash = sha1( 'baosdfhasdfj' );

		return [
			'unknown statement guid' => [ $item, $badGuid, $goodReference, '' ],
			'unknown reference hash' => [ $item, $goodGuid, $goodReference, $badRefHash ],
		];
	}

	/**
	 * @dataProvider provideApplyInvalid
	 */
	public function testApplyInvalid(
		EntityDocument $entity,
		$guid,
		Reference $reference,
		$referenceHash
	) {
		$this->expectException( ChangeOpException::class );

		$changeOpReference = new ChangeOpReference(
			$guid,
			$reference,
			$referenceHash,
			$this->mockProvider->getMockSnakValidator()
		);

		$changeOpReference->apply( $entity );
	}

	public function provideValidate() {
		$p11 = new NumericPropertyId( 'P11' );
		$q17 = new ItemId( 'Q17' );

		$item = new Item( $q17 );
		$guid = 'GUID';

		$oldSnak = new PropertyValueSnak( $p11, new StringValue( "old reference" ) );
		$oldReference = new Reference( new SnakList( [ $oldSnak ] ) );

		$snak = new PropertyNoValueSnak( $p11 );
		$qualifiers = new SnakList( [ $oldSnak ] );
		$item->getStatements()->addNewStatement( $snak, $qualifiers, null, $guid );

		//NOTE: the mock validator will consider the string "INVALID" to be invalid.
		$badSnak = new PropertyValueSnak( $p11, new StringValue( 'INVALID' ) );
		$brokenSnak = new PropertyValueSnak( $p11, new NumberValue( 23 ) );

		$badReference = new Reference( new SnakList( [ $badSnak ] ) );
		$brokenReference = new Reference( new SnakList( [ $brokenSnak ] ) );

		$refHash = $oldReference->getHash();

		return [
			'invalid snak value' => [ $item, $guid, $badReference, '' ],
			'invalid snak value type' => [ $item, $guid, $brokenReference, $refHash ],
		];
	}

	/**
	 * @dataProvider provideValidate
	 */
	public function testValidate(
		EntityDocument $entity,
		$guid,
		Reference $reference,
		$referenceHash
	) {
		$changeOpReference = new ChangeOpReference(
			$guid,
			$reference,
			$referenceHash,
			$this->mockProvider->getMockSnakValidator()
		);

		$result = $changeOpReference->validate( $entity );
		$this->assertFalse( $result->isValid(), 'isValid()' );
	}

	public function testGetActions() {
		$changeOp = new ChangeOpReference(
			'guid',
			new Reference( new SnakList( [] ) ),
			'refhash',
			$this->mockProvider->getMockSnakValidator()
		);

		$this->assertEquals( [ EntityPermissionChecker::ACTION_EDIT ], $changeOp->getActions() );
	}

}
