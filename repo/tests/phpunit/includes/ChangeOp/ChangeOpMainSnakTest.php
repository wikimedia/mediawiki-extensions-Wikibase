<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use DataValues\DataValue;
use DataValues\NumberValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\ChangeOpMainSnak;
use Wikibase\Repo\Store\EntityPermissionChecker;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangeOpMainSnak
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpMainSnakTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var ChangeOpTestMockProvider
	 */
	protected $mockProvider;

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
		$validSnak = new PropertyValueSnak( 7201010, new StringValue( 'o_O' ) );

		$args = [];
		$args[] = [ 123, $validSnak ];
		$args[] = [ false, $validSnak ];

		return $args;
	}

	/**
	 * @dataProvider invalidArgumentProvider
	 */
	public function testInvalidConstruct( $guid, $snak ) {
		$this->expectException( InvalidArgumentException::class );
		new ChangeOpMainSnak(
			$guid,
			$snak,
			new GuidGenerator(),
			$this->mockProvider->getMockSnakValidator()
		);
	}

	/**
	 * @param string $guid
	 * @param Snak $snak
	 *
	 * @return ChangeOpMainSnak
	 */
	protected function newChangeOpMainSnak( $guid, Snak $snak ) {
		return new ChangeOpMainSnak(
			$guid,
			$snak,
			new GuidGenerator(),
			$this->mockProvider->getMockSnakValidator()
		);
	}

	public function provideChangeOps() {
		$snak = $this->makeSnak( 'P5', 'test' );
		$args = [];

		// add a new claim
		$item = $this->makeNewItemWithClaim( 'Q123', $snak );
		$newSnak = $this->makeSnak( 'P8', 'newSnak' );
		$guid = '';
		$changeOp = $this->newChangeOpMainSnak( $guid, $newSnak );
		$expected = $newSnak->getDataValue();
		$args['add new claim'] = [ $item, $changeOp, $expected ];

		// update an existing claim with a new main snak value
		$item = $this->makeNewItemWithClaim( 'Q234', $snak );
		$newSnak = $this->makeSnak( 'P5', 'changedSnak' );
		$statements = $item->getStatements()->toArray();

		$guid = $statements[0]->getGuid();
		$changeOp = $this->newChangeOpMainSnak( $guid, $newSnak );
		$expected = $newSnak->getDataValue();
		$args['update claim by guid'] = [ $item, $changeOp, $expected ];

		return $args;
	}

	/**
	 * @dataProvider provideChangeOps
	 */
	public function testApply( Item $item, ChangeOpMainSnak $changeOp, DataValue $expected = null ) {
		$changeOpResult = $changeOp->apply( $item );
		$this->assertNotEmpty( $changeOp->getStatementGuid() );
		$statements = $item->getStatements();
		$statement = $statements->getFirstStatementWithGuid( $changeOp->getStatementGuid() );
		if ( $expected === null ) {
			$this->assertNull( $statement );
		} else {
			$this->assertEquals( $expected, $statement->getMainSnak()->getDataValue() );
		}
		// this module always changes the entity, adding or updating main snak value
		$this->assertTrue( $changeOpResult->isEntityChanged() );
	}

	public function provideInvalidApply() {
		$snak = $this->makeSnak( 'P11', 'test' );
		$item = $this->makeNewItemWithClaim( 'Q777', $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$guid = $statement->getGuid();

		// apply change to the wrong item
		$wrongItem = new Item( new ItemId( 'Q888' ) );
		$newSnak = $this->makeSnak( 'P12', 'newww' );
		$args['wrong entity'] = [ $wrongItem, $this->newChangeOpMainSnak( $guid, $newSnak ) ];

		// apply change to an unknown claim
		$wrongClaimId = $item->getId()->getSerialization() . '$DEADBEEF-DEAD-BEEF-DEAD-BEEFDEADBEEF';
		$args['unknown claim'] = [ $item, $this->newChangeOpMainSnak( $wrongClaimId, $newSnak ) ];

		// update an existing claim with wrong main snak property
		$newSnak = $this->makeSnak( 'P13', 'changedSnak' );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );

		$guid = $statement->getGuid();
		$changeOp = $this->newChangeOpMainSnak( $guid, $newSnak );
		$args['wrong main snak property'] = [ $item, $changeOp ];

		// apply invalid main snak
		$badSnak = $this->makeSnak( 'P12', new NumberValue( 5 ) );
		$args['bad value type'] = [ $wrongItem, $this->newChangeOpMainSnak( $guid, $badSnak ) ];

		// apply invalid main snak
		// NOTE: the mock validator considers "INVALID" to be invalid.
		$badSnak = $this->makeSnak( 'P12', 'INVALID' );
		$args['invalid value'] = [ $wrongItem, $this->newChangeOpMainSnak( $guid, $badSnak ) ];

		return $args;
	}

	/**
	 * @dataProvider provideInvalidApply
	 */
	public function testInvalidApply( EntityDocument $item, ChangeOp $changeOp ) {
		$this->expectException( ChangeOpException::class );

		$changeOp->apply( $item );
	}

	private function makeNewItemWithClaim( $itemIdString, $snak ) {
		$item = new Item( new ItemId( $itemIdString ) );

		$item->getStatements()->addNewStatement( $snak, null, null, 'GUID' );

		return $item;
	}

	private function makeSnak( $propertyId, $value ) {
		if ( is_string( $propertyId ) ) {
			$propertyId = new NumericPropertyId( $propertyId );
		}

		if ( is_string( $value ) ) {
			$value = new StringValue( $value );
		}

		if ( $value === null ) {
			return new PropertyNoValueSnak( $propertyId );
		} else {
			return new PropertyValueSnak( $propertyId, $value );
		}
	}

	public function validateProvider() {
		$p11 = new NumericPropertyId( 'P11' );
		$q17 = new ItemId( 'Q17' );

		//NOTE: the mock validator will consider the string "INVALID" to be invalid.
		$badSnak = new PropertyValueSnak( $p11, new StringValue( 'INVALID' ) );
		$brokenSnak = new PropertyValueSnak( $p11, new NumberValue( 23 ) );

		$guidGenerator = new GuidGenerator();

		$cases = [];

		$guid = $guidGenerator->newGuid( $q17 );
		$cases['bad snak value'] = [ $q17, $guid, $badSnak ];

		$guid = $guidGenerator->newGuid( $q17 );
		$cases['broken snak'] = [ $q17, $guid, $brokenSnak ];

		return $cases;
	}

	/**
	 * @dataProvider validateProvider
	 */
	public function testValidate( ItemId $entityId, $guid, Snak $snak ) {
		$changeOpMainSnak = new ChangeOpMainSnak(
			$guid,
			$snak,
			new GuidGenerator(),
			$this->mockProvider->getMockSnakValidator()
		);

		$entity = new Item( $entityId );

		$result = $changeOpMainSnak->validate( $entity );
		$this->assertFalse( $result->isValid(), 'isValid()' );
	}

	public function testGetActions() {
		$changeOp = $this->newChangeOpMainSnak( 'guid', $this->makeSnak( 'P11', 'test' ) );

		$this->assertEquals( [ EntityPermissionChecker::ACTION_EDIT ], $changeOp->getActions() );
	}

}
