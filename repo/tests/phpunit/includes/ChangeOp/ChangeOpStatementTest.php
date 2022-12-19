<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use DataValues\NumberValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\ChangeOpStatement;
use Wikibase\Repo\Store\EntityPermissionChecker;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangeOpStatement
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpStatementTest extends \PHPUnit\Framework\TestCase {

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

	/**
	 * @dataProvider invalidIndexProvider
	 */
	public function testConstructionWithInvalidIndex( $invalidIndex ) {
		$this->expectException( InvalidArgumentException::class );

		$this->newChangeOpStatement(
			$this->mockProvider->makeStatement( 'P7' ),
			$invalidIndex
		);
	}

	public function invalidIndexProvider() {
		return [
			[ false ],
			[ -1 ],
			[ 1.0 ],
			[ '1' ],
			[ '' ],
		];
	}

	/**
	 * @dataProvider provideTestApply
	 *
	 * @param StatementListProvider $entity
	 * @param Statement $statement
	 * @param Statement[] $expected
	 * @param int|null $index
	 */
	public function testApply( StatementListProvider $entity, Statement $statement, array $expected,
		$index = null
	) {
		$changeOpStatement = $this->newChangeOpStatement( $statement, $index );
		$changeOpResult = $changeOpStatement->apply( $entity );

		$expectedStatementList = new StatementList( ...$expected );
		$this->assertEquals( $expectedStatementList, $entity->getStatements() );
		$this->assertTrue( $entity->getStatements()->equals( $expectedStatementList ) );
		$this->assertTrue( $changeOpResult->isEntityChanged() );
	}

	public function provideTestApply() {
		$item777 = $this->makeNewItemWithStatement( 'Q777', new PropertyNoValueSnak( 45 ) );
		$item666 = $this->makeNewItemWithStatement( 'Q666', new PropertySomeValueSnak( 44 ) );

		$item777Statements = $item777->getStatements()->toArray();
		$item666Statements = $item666->getStatements()->toArray();

		$statement777 = reset( $item777Statements );
		$statement666 = reset( $item666Statements );

		// statements that exist on the given entities
		$statements[0] = new Statement( new PropertyNoValueSnak( 43 ) );
		$statements[777] = clone $statement777;
		$statements[666] = clone $statement666;

		// statements with a null guid
		$statements[7770] = clone $statement777;
		$statements[7770]->setGuid( null );
		$statements[6660] = clone $statement666;
		$statements[6660]->setGuid( null );

		// new statements not yet on the entity
		$statements[7777] = clone $statement777;
		$statements[7777]->setGuid( 'Q777$D8404CDA-25E4-4334-AF13-A3290BC77777' );
		$statements[6666] = clone $statement666;
		$statements[6666]->setGuid( 'Q666$D8404CDA-25E4-4334-AF13-A3290BC66666' );

		$statements[11] = new Statement( new PropertyNoValueSnak( 1 ) );
		$statements[11]->setGuid( null );
		$statements[12] = new Statement( new PropertySomeValueSnak( 1 ) );
		$statements[12]->setGuid( null );
		$statements[13] = clone $statements[12];
		$statements[13]->setGuid( 'Q666$D8404CDA-25E4-4334-AF13-A3290BC66613' );

		$args = [];

		// test adding the same statements with a null guid (a guid should be created)
		$args[] = [
			$item777,
			$statements[7770],
			[ $statements[777], $statements[7770] ],
		];

		$args[] = [
			$item666,
			$statements[6660],
			[ $statements[666], $statements[6660] ],
		];

		// test adding the same statements with a correct but different guid (these should be added)
		$args[] = [
			$item777,
			$statements[7777],
			[ $statements[777], $statements[7770], $statements[7777] ],
		];

		$args[] = [
			$item666,
			$statements[6666],
			[ $statements[666], $statements[6660], $statements[6666] ],
		];

		// test adding the same statements with and id that already exists (these shouldn't be added)
		$args[] = [
			$item777,
			$statements[7777],
			[ $statements[777], $statements[7770], $statements[7777] ],
		];

		$args[] = [
			$item666,
			$statements[6666],
			[ $statements[666], $statements[6660], $statements[6666] ],
		];

		// test adding a statement at a specific index
		$args[] = [
			$item777,
			$statements[0],
			[ $statements[0], $statements[777], $statements[7770], $statements[7777] ],
			0,
		];

		// test moving a statement
		$args[] = [
			$item666,
			$statements[6666],
			[ $statements[666], $statements[6666], $statements[6660] ],
			1,
		];

		// test adding a statement featuring another property id within the boundaries of statements the
		// same property
		$args[] = [
			$item666,
			$statements[11],
			[ $statements[666], $statements[6666], $statements[6660], $statements[11] ],
			1,
		];

		// test moving a subset of statements featuring the same property
		$args[] = [
			$item666,
			$statements[12],
			[
				$statements[12],
				$statements[11],
				$statements[666],
				$statements[6666],
				$statements[6660],
			],
			0,
		];

		$args['StatementListProvider support'] = [
			new StatementListProviderDummy( 'Q777' ),
			$statement777,
			[ $statement777 ],
		];

		return $args;
	}

	public function testApplyWithProperty() {
		$property = Property::newFromType( 'string' );
		$property->setId( new NumericPropertyId( 'P73923' ) );

		$statement = new Statement( new PropertyNoValueSnak( 45 ) );
		$expected = new StatementList( $statement );

		$changeOpStatement = $this->newChangeOpStatement( $statement );
		$changeOpResult = $changeOpStatement->apply( $property );

		$this->assertTrue( $property->getStatements()->equals( $expected ) );
		$this->assertTrue( $changeOpResult->isEntityChanged() );
	}

	/**
	 * @dataProvider applyInvalidThrowsExceptionProvider
	 */
	public function testApplyInvalidThrowsException( Item $item, Statement $statement ) {
		$this->expectException( ChangeOpException::class );

		$changeOpStatement = $this->newChangeOpStatement( $statement );
		$changeOpStatement->apply( $item );
	}

	public function applyInvalidThrowsExceptionProvider() {
		$itemEmpty = new Item( new ItemId( 'Q888' ) );
		$noValueSnak = new PropertyNoValueSnak( new NumericPropertyId( 'P45' ) );
		$someValueSnak = new PropertySomeValueSnak( new NumericPropertyId( 'P44' ) );

		$statementWithInvalidGuid = new Statement( $noValueSnak );
		$statementWithInvalidGuid->setGuid( 'Q0$' );

		$item777 = $this->makeNewItemWithStatement( 'Q777', $noValueSnak );
		$item666 = $this->makeNewItemWithStatement( 'Q666', $someValueSnak );

		$item777Statements = $item777->getStatements()->toArray();
		$item666Statements = $item666->getStatements()->toArray();

		$statement777 = reset( $item777Statements );
		$statement666 = reset( $item666Statements );

		// statements that exist on the given entities
		$statements[777] = clone $statement777;
		$statements[666] = clone $statement666;

		// test adding statements with guids from other items (these shouldn't be added)
		return [
			[ $itemEmpty, $statementWithInvalidGuid ],
			[ $itemEmpty, $statements[666] ],
			[ $itemEmpty, $statements[777] ],
			[ $item666, $statements[777] ],
			[ $item777, $statements[666] ],
		];
	}

	/**
	 * @param Statement $statement
	 * @param int|null $index
	 *
	 * @return ChangeOpStatement
	 */
	private function newChangeOpStatement( Statement $statement, $index = null ) {
		$idParser = new BasicEntityIdParser();

		return new ChangeOpStatement(
			$statement,
			new GuidGenerator(),
			new StatementGuidValidator( $idParser ),
			new StatementGuidParser( $idParser ),
			$this->mockProvider->getMockSnakValidator(),
			$index
		);
	}

	public function provideInvalidApply() {
		$snak = new PropertyNoValueSnak( 67573284 );
		$item = $this->makeNewItemWithStatement( 'Q777', $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );

		// change main snak to "some value"
		$newSnak = new PropertySomeValueSnak( 67573284 );
		$newStatement = clone $statement;
		$newStatement->setMainSnak( $newSnak );

		// apply change to the wrong item
		$wrongItem = new Item( new ItemId( 'Q888' ) );
		$args['wrong entity'] = [ $wrongItem, $this->newChangeOpStatement( $newStatement ) ];

		// update an existing statement with wrong main snak property
		$newSnak = new PropertyNoValueSnak( 23452345 );
		$newStatement->setMainSnak( $newSnak );

		$changeOp = $this->newChangeOpStatement( $newStatement );

		$args['wrong main snak property'] = [ $item, $changeOp ];

		return $args;
	}

	/**
	 * @dataProvider provideInvalidApply
	 */
	public function testInvalidApply( EntityDocument $item, ChangeOpStatement $changeOp ) {
		$this->expectException( ChangeOpException::class );

		$changeOp->apply( $item );
	}

	/**
	 * @param string $idString
	 * @param Snak $mainSnak
	 *
	 * @return Item
	 */
	private function makeNewItemWithStatement( $idString, Snak $mainSnak ) {
		$id = new ItemId( $idString );

		$statement = new Statement( $mainSnak );
		$guidGenerator = new GuidGenerator();
		$statement->setGuid( $guidGenerator->newGuid( $id ) );

		$item = new Item( $id );
		$item->getStatements()->addStatement( $statement );
		return $item;
	}

	public function validateProvider() {
		$p11 = new NumericPropertyId( 'P11' );
		$q17 = new ItemId( 'Q17' );

		//NOTE: the mock validator will consider the string "INVALID" to be invalid.
		$goodSnak = new PropertyValueSnak( $p11, new StringValue( 'good' ) );
		$badSnak = new PropertyValueSnak( $p11, new StringValue( 'INVALID' ) );
		$brokenSnak = new PropertyValueSnak( $p11, new NumberValue( 23 ) );

		$guidGenerator = new GuidGenerator();

		$cases = [];

		$statement = new Statement( $badSnak );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$cases['invalid value in main snak'] = [ $q17, $statement ];

		$statement = new Statement( $brokenSnak );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$cases['mismatching value in main snak'] = [ $q17, $statement ];

		$statement = new Statement( $goodSnak );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$statement->setQualifiers( new SnakList( [ $badSnak ] ) );
		$cases['bad snak in qualifiers'] = [ $q17, $statement ];

		$statement = new Statement( $goodSnak );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$statement->setQualifiers( new SnakList( [ $brokenSnak ] ) );
		$cases['mismatching value in qualifier'] = [ $q17, $statement ];

		$statement = new Statement( $goodSnak );
		$reference = new Reference( new SnakList( [ $badSnak ] ) );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$statement->setReferences( new ReferenceList( [ $reference ] ) );
		$cases['bad snak in reference'] = [ $q17, $statement ];

		$statement = new Statement( $goodSnak );
		$reference = new Reference( new SnakList( [ $badSnak ] ) );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$statement->setReferences( new ReferenceList( [ $reference ] ) );
		$cases['mismatching value in reference'] = [ $q17, $statement ];

		return $cases;
	}

	/**
	 * @dataProvider validateProvider
	 */
	public function testValidate( ItemId $itemId, Statement $statement ) {
		$changeOpStatement = $this->newChangeOpStatement( $statement, 0 );

		$item = new Item( $itemId );

		$result = $changeOpStatement->validate( $item );
		$this->assertFalse( $result->isValid(), 'isValid()' );
	}

	public function testGetActions() {
		$changeOp = $this->newChangeOpStatement( new Statement( new PropertyNoValueSnak( 1 ) ) );

		$this->assertEquals( [ EntityPermissionChecker::ACTION_EDIT ], $changeOp->getActions() );
	}

}
