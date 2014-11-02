<?php

namespace Wikibase\Test;

use DataValues\NumberValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOpClaim;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\ClaimGuidParser;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Lib\ClaimGuidValidator;

/**
 * @covers Wikibase\ChangeOp\ChangeOpClaim
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 * @group ChangeOpClaim
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Daniel Kinzler
 */
class ChangeOpClaimTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var ChangeOpTestMockProvider
	 */
	protected $mockProvider;

	/**
	 * @param string|null $name
	 * @param array $data
	 * @param string $dataName
	 */
	public function __construct( $name = null, array $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->mockProvider = new ChangeOpTestMockProvider( $this );
	}

	public function invalidIndexProvider() {
		return array(
			array( 'foo' ),
			array( array() ),
			array( $this->mockProvider->makeStatement( 'P7' ) ),
		);
	}

	/**
	 * @dataProvider invalidIndexProvider
	 */
	public function testConstructionWithInvalidIndex( $invalidIndex ) {
		$this->setExpectedException( 'InvalidArgumentException' );

		$changeOpClaim = $this->newChangeOpClaim(
			$this->mockProvider->makeStatement( 'P7' ),
			$invalidIndex
		);
	}

	public function provideTestApply() {
		$itemEmpty = Item::newEmpty();
		$itemEmpty->setId( new ItemId( 'Q888' ) );

		$item777 = $this->makeNewItemWithStatement( 'Q777', new PropertyNoValueSnak( 45 ) );
		$item666 = $this->makeNewItemWithStatement( 'Q666', new PropertySomeValueSnak( 44 ) );

		$item777Statements = $item777->getClaims();
		$item666Statements = $item666->getClaims();

		$statement777 = reset( $item777Statements );
		$statement666 = reset( $item666Statements );

		// claims that exist on the given entities
		$statements[0] = new Statement( new Claim( new PropertyNoValueSnak( 43 ) ) );
		$statements[777] = clone $statement777;
		$statements[666] = clone $statement666;

		// claims with a null guid
		$statements[7770] = clone $statement777;
		$statements[7770]->setGuid( null );
		$statements[6660] = clone $statement666;
		$statements[6660]->setGuid( null );

		// new claims not yet on the entity
		$statements[7777] = clone $statement777;
		$statements[7777]->setGuid( 'Q777$D8404CDA-25E4-4334-AF13-A3290BC77777' );
		$statements[6666] = clone $statement666;
		$statements[6666]->setGuid( 'Q666$D8404CDA-25E4-4334-AF13-A3290BC66666' );

		$statements[11] = new Statement( new Claim( new PropertyNoValueSnak( 1 ) ) );
		$statements[11]->setGuid( null );
		$statements[12] = new Statement( new Claim( new PropertySomeValueSnak( 1 ) ) );
		$statements[12]->setGuid( null );
		$statements[13] = clone $statements[12];
		$statements[13]->setGuid( 'Q666$D8404CDA-25E4-4334-AF13-A3290BC66613' );

		$args = array();

		// test adding the same claims with a null guid (a guid should be created)
		$args[] = array(
			$item777,
			$statements[7770],
			new StatementList( array( $statements[777], $statements[7770] ) )
		);

		$args[] = array(
			$item666,
			$statements[6660],
			new StatementList( array( $statements[666], $statements[6660] ) )
		);

		// test adding the same claims with a correct but different guid (these should be added)
		$args[] = array(
			$item777,
			$statements[7777],
			new StatementList( array( $statements[777], $statements[7770], $statements[7777] ) )
		);

		$args[] = array(
			$item666,
			$statements[6666],
			new StatementList( array( $statements[666], $statements[6660], $statements[6666] ) )
		);

		// test adding the same claims with and id that already exists (these shouldn't be added)
		$args[] = array(
			$item777,
			$statements[7777],
			new StatementList( array( $statements[777], $statements[7770], $statements[7777] ) )
		);

		$args[] = array(
			$item666,
			$statements[6666],
			new StatementList( array( $statements[666], $statements[6660], $statements[6666] ) )
		);

		// test adding a claim at a specific index
		$args[] = array(
			$item777,
			$statements[0],
			new StatementList( array(
				$statements[0],
				$statements[777],
				$statements[7770],
				$statements[7777]
			) ),
			0
		);

		// test moving a claim
		$args[] = array(
			$item666,
			$statements[6666],
			new StatementList( array( $statements[666], $statements[6666], $statements[6660] ) ),
			1
		);

		// test adding a claim featuring another property id within the boundaries of claims the
		// same property
		$args[] = array(
			$item666,
			$statements[11],
			new StatementList( array(
				$statements[666],
				$statements[6666],
				$statements[6660],
				$statements[11]
			) ),
			1
		);

		// test moving a subset of claims featuring the same property
		$args[] = array(
			$item666,
			$statements[12],
			new StatementList( array(
				$statements[12],
				$statements[11],
				$statements[666],
				$statements[6666],
				$statements[6660]
			) ),
			0
		);

		return $args;
	}

	/**
	 * @dataProvider provideTestApply
	 *
	 * @param Entity $entity
	 * @param Statement $statement
	 * @param StatementList $expected
	 * @param int|null $index
	 */
	public function testApply( Entity $entity, Statement $statement, StatementList $expected,
		$index = null
	) {
		$changeOpClaim = $this->newChangeOpClaim( $statement, $index );
		$changeOpClaim->apply( $entity );

		$this->assertTrue( $entity->getStatements()->equals( $expected ) );
	}

	public function testApplyWithProperty() {
		$property = Property::newEmpty();
		$property->setId( new PropertyId( 'P73923' ) );

		$claim = $this->makeClaim( $property, new PropertyNoValueSnak( 45 ) );
		$statement = new Statement( $claim );
		$expected = new StatementList( array( $statement ) );

		$changeOpClaim = $this->newChangeOpClaim( $statement );
		$changeOpClaim->apply( $property );

		$this->assertTrue( $property->getStatements()->equals( $expected ) );
	}

	/**
	 * @dataProvider applyInvalidThrowsExceptionProvider
	 *
	 * @param Entity $entity
	 * @param Statement $statement
	 */
	public function testApplyInvalidThrowsException( Entity $entity, Statement $statement ) {
		$this->setExpectedException( '\Wikibase\ChangeOp\ChangeOpException' );

		$changeOpClaim = $this->newChangeOpClaim( $statement );
		$changeOpClaim->apply( $entity );
	}

	public function applyInvalidThrowsExceptionProvider() {
		$itemEmpty = Item::newEmpty();
		$itemEmpty->setId( new ItemId( 'Q888' ) );

		$item777 = $this->makeNewItemWithStatement( 'Q777', new PropertyNoValueSnak( 45 ) );
		$item666 = $this->makeNewItemWithStatement( 'Q666', new PropertySomeValueSnak( 44 ) );

		$item777Statements = $item777->getClaims();
		$item666Statements = $item666->getClaims();

		$statement777 = reset( $item777Statements );
		$statement666 = reset( $item666Statements );

		// claims that exist on the given entities
		$statements[777] = clone $statement777;
		$statements[666] = clone $statement666;

		// test adding claims with guids from other items (these shouldn't be added)
		return array(
			array( $itemEmpty, $statements[666] ),
			array( $itemEmpty, $statements[777] ),
			array( $item666, $statements[777] ),
			array( $item777, $statements[666] ),
		);
	}

	/**
	 * @param Statement $statement
	 * @param index|null $index
	 *
	 * @return ChangeOpClaim
	 */
	private function newChangeOpClaim( Statement $statement, $index = null ) {
		$idParser = new BasicEntityIdParser();

		return new ChangeOpClaim(
			$statement,
			new ClaimGuidGenerator(),
			new ClaimGuidValidator( $idParser ),
			new ClaimGuidParser( $idParser ),
			$this->mockProvider->getMockSnakValidator(),
			$index
		);
	}

	public function provideInvalidApply() {
		$snak = new PropertyNoValueSnak( 67573284 );
		$item = $this->makeNewItemWithStatement( 'Q777', $snak );
		$statements = $item->getClaims();
		$statement = reset( $statements );

		// change main snak to "some value"
		$newSnak = new PropertySomeValueSnak( 67573284 );
		$newStatement = clone $statement;
		$newStatement->setMainSnak( $newSnak );

		// apply change to the wrong item
		$wrongItem = Item::newEmpty();
		$wrongItem->setId( new ItemId( "Q888" ) );
		$args['wrong entity'] = array ( $wrongItem, $this->newChangeOpClaim( $newStatement ) );

		// update an existing claim with wrong main snak property
		$newSnak = new PropertyNoValueSnak( 23452345 );
		$newStatement->setMainSnak( $newSnak );

		$changeOp = $this->newChangeOpClaim( $newStatement );

		$args['wrong main snak property'] = array ( $item, $changeOp );

		return $args;
	}

	/**
	 * @dataProvider provideInvalidApply
	 */
	public function testInvalidApply( Entity $item, ChangeOpClaim $changeOp ) {
		$this->setExpectedException( 'Wikibase\ChangeOp\ChangeOpException' );

		$changeOp->apply( $item );
	}

	/**
	 * @param integer $itemId
	 * @param Snak $snak
	 * @return Item
	 */
	private function makeNewItemWithStatement( $itemId, Snak $snak ) {
		$item = Item::newEmpty();
		$item->setId( new ItemId( $itemId ) );

		$this->addClaimsToEntity( $item, $snak );

		return $item;
	}

	/**
	 * @param Entity $entity
	 */
	private function makeClaim( Entity $entity, Snak $snak ) {
		$claim = $entity->newClaim( $snak );
		$guidGenerator = new ClaimGuidGenerator();
		$claim->setGuid( $guidGenerator->newGuid( $entity->getId() ) );

		return $claim;
	}

	/**
	 * @param Entity $entity
	 * @param Snak $snak
	 */
	private function addClaimsToEntity( Entity $entity, Snak $snak ) {
		$claim = $this->makeClaim( $entity, $snak );

		$entity->getStatements()->addStatement( new Statement( $claim ) );
	}

	public function validateProvider() {
		$p11 = new PropertyId( 'P11' );
		$q17 = new ItemId( 'Q17' );

		//NOTE: the mock validator will consider the string "INVALID" to be invalid.
		$goodSnak = new PropertyValueSnak( $p11, new StringValue( 'good' ) );
		$badSnak = new PropertyValueSnak( $p11, new StringValue( 'INVALID' ) );
		$brokenSnak = new PropertyValueSnak( $p11, new NumberValue( 23 ) );

		$guidGenerator = new ClaimGuidGenerator();

		$cases = array();

		$statement = new Statement( new Claim( $badSnak ) );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$cases['invalid value in main snak'] = array( $q17, $statement );

		$statement = new Statement( new Claim( $brokenSnak ) );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$cases['mismatching value in main snak'] = array( $q17, $statement );

		$statement = new Statement( new Claim( $goodSnak ) );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$statement->setQualifiers( new SnakList( array( $badSnak ) ) );
		$cases['bad snak in qualifiers'] = array( $q17, $statement );

		$statement = new Statement( new Claim( $goodSnak ) );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$statement->setQualifiers( new SnakList( array( $brokenSnak ) ) );
		$cases['mismatching value in qualifier'] = array( $q17, $statement );

		$statement = new Statement( new Claim( $goodSnak ) );
		$reference = new Reference( new SnakList( array( $badSnak ) ) );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$statement->setReferences( new ReferenceList( array( $reference ) ) );
		$cases['bad snak in reference'] = array( $q17, $statement );

		$statement = new Statement( new Claim( $goodSnak ) );
		$reference = new Reference( new SnakList( array( $badSnak ) ) );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$statement->setReferences( new ReferenceList( array( $reference ) ) );
		$cases['mismatching value in reference'] = array( $q17, $statement );

		return $cases;
	}

	/**
	 * @dataProvider validateProvider
	 */
	public function testValidate( EntityId $entityId, Statement $statement ) {
		$changeOpClaim = $this->newChangeOpClaim( $statement, 0 );

		$entity = Item::newEmpty();
		$entity->setId( $entityId );

		$result = $changeOpClaim->validate( $entity );
		$this->assertFalse( $result->isValid(), 'isValid()' );
	}

}
