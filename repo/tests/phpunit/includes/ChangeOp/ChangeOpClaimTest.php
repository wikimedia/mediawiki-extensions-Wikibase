<?php

namespace Wikibase\Test;

use DataValues\NumberValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOpClaim;
use Wikibase\DataModel\Claim\ClaimGuidParser;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
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
	 * @expectedException InvalidArgumentException
	 */
	public function testConstructionWithInvalidIndex( $invalidIndex ) {
		$itemId = new ItemId( 'q42' );
		new ChangeOpClaim(
			$this->mockProvider->makeStatement( 'P7' ),
			$this->mockProvider->getGuidGenerator(),
			$this->mockProvider->getMockGuidValidator(),
			$this->mockProvider->getMockGuidParser( $itemId ),
			$this->mockProvider->getMockSnakValidator(),
			$invalidIndex
		);
	}

	public function provideTestApply() {
		$itemEmpty = Item::newEmpty();
		$itemEmpty->setId( new ItemId( 'q888' ) );
		$item777 = self::makeNewItemWithStatement( 'Q777', new PropertyNoValueSnak( 45 ) );
		$item666 = self::makeNewItemWithStatement( 'Q666', new PropertySomeValueSnak( 44 ) );

		$item777Statements = $item777->getClaims();
		$item666Statements = $item666->getClaims();

		$statement777 = reset( $item777Statements );
		$statement666 = reset( $item666Statements );

		//claims that exist on the given entities
		$statements[0] = new Statement( new PropertyNoValueSnak( 43 ) );
		$statements[777] = clone $statement777;
		$statements[666] = clone $statement666;
		//claims with a null guid
		$statements[7770] = clone $statement777;
		$statements[7770]->setGuid( null );
		$statements[6660] = clone $statement666;
		$statements[6660]->setGuid( null );
		//new claims not yet on the entity
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

		$args = array();
		//test adding claims with guids from other items(these shouldn't be added)
		$args[] = array( $itemEmpty, $statements[666], false );
		$args[] = array( $itemEmpty, $statements[777], false );
		$args[] = array( $item666, $statements[777], false );
		$args[] = array( $item777, $statements[666], false );
		//test adding the same claims with a null guid (a guid should be created)
		$args[] = array( $item777, $statements[7770], array( $statements[777], $statements[7770] ) );
		$args[] = array( $item666, $statements[6660], array( $statements[666], $statements[6660] ) );
		//test adding the same claims with a correct but different guid (these should be added)
		$args[] = array( $item777, $statements[7777], array( $statements[777], $statements[7770], $statements[7777] ) );
		$args[] = array( $item666, $statements[6666], array( $statements[666], $statements[6660], $statements[6666] ) );
		//test adding the same claims with and id that already exists (these shouldn't be added)
		$args[] = array( $item777, $statements[7777], array( $statements[777], $statements[7770], $statements[7777] ) );
		$args[] = array( $item666, $statements[6666], array( $statements[666], $statements[6660], $statements[6666] ) );
		// test adding a claim at a specific index
		$args[] = array( $item777, $statements[0], array( $statements[0], $statements[777], $statements[7770], $statements[7777] ), 0 );
		// test moving a claim
		$args[] = array( $item666, $statements[6666], array( $statements[666], $statements[6666], $statements[6660] ), 1 );
		// test adding a claim featuring another property id within the boundaries of claims the
		// same property
		$args[] = array( $item666, $statements[11], array( $statements[666], $statements[6666], $statements[6660], $statements[11] ), 1 );
		// test moving a subset of claims featuring the same property
		$args[] = array( $item666, $statements[12], array( $statements[12], $statements[11], $statements[666], $statements[6666], $statements[6660] ), 0 );

		return $args;
	}

	/**
	 * @dataProvider provideTestApply
	 *
	 * @param Entity $entity
	 * @param Statement $statement
	 * @param Statement[]|bool $expected
	 * @param int|null $index
	 */
	public function testApply( Entity $entity, Statement $statement, $expected, $index = null ) {
		if ( $expected === false ) {
			$this->setExpectedException( '\Wikibase\ChangeOp\ChangeOpException' );
		}

		$idParser = new BasicEntityIdParser();
		$changeOpClaim = new ChangeOpClaim(
			$statement,
			new ClaimGuidGenerator(),
			new ClaimGuidValidator( $idParser ),
			new ClaimGuidParser( $idParser ),
			$this->mockProvider->getMockSnakValidator(),
			$index
		);

		$changeOpClaim->apply( $entity );

		if ( $expected === false ) {
			$this->fail( 'Failed to throw a ChangeOpException' );
		}

		$entityClaims = new Claims( $entity->getClaims() );
		$entityClaimHashSet = array_flip( $entityClaims->getHashes() );
		$i = 0;

		foreach ( $expected as $expectedStatement ) {
			$guid = $expectedStatement->getGuid();
			$hash = $expectedStatement->getHash();

			if ( $guid !== null ) {
				$this->assertEquals( $i++, $entityClaims->indexOf( $expectedStatement ) );
			}

			$this->assertArrayHasKey( $hash, $entityClaimHashSet );
		}

		$this->assertEquals( count( $expected ), $entityClaims->count() );
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
		$args['wrong entity'] = array ( $wrongItem, new ChangeOpClaim(
			$newStatement,
			$this->mockProvider->getGuidGenerator(),
			$this->mockProvider->getMockGuidValidator(),
			$this->mockProvider->getMockGuidParser( $item->getId() ),
			$this->mockProvider->getMockSnakValidator()
		) );

		//TODO: once we stop allowing user-generated GUIDs for new claims, test this below.
		// apply change to an unknown claim
		/*
		$wrongClaimId = $item->getId()->getSerialization() . '$DEADBEEF-DEAD-BEEF-DEAD-BEEFDEADBEEF';
		$badClaim = clone $newClaim;
		$badClaim->setGuid( $wrongClaimId );
		$args['unknown claim'] = array ( $item, new ChangeOpClaim( $badClaim, $guidGenerator ) );
		*/

		// update an existing claim with wrong main snak property
		$newSnak = new PropertyNoValueSnak( 23452345 );
		$newStatement->setMainSnak( $newSnak );

		$changeOp =  new ChangeOpClaim(
			$newStatement,
			$this->mockProvider->getGuidGenerator(),
			$this->mockProvider->getMockGuidValidator(),
			$this->mockProvider->getMockGuidParser( $item->getId() ),
			$this->mockProvider->getMockSnakValidator()
		);

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
	 * @param $snak
	 * @return Item
	 */
	protected function makeNewItemWithStatement( $itemId, $snak ) {
		$item = Item::newEmpty();
		$item->setId( new ItemId( $itemId ) );

		$statement = $item->newClaim( $snak );
		$guidGenerator = new ClaimGuidGenerator();
		$statement->setGuid( $guidGenerator->newGuid( $item->getId() ) );

		$claims = new Claims();
		$claims->addClaim( $statement );
		$item->setClaims( $claims );

		return $item;
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

		$statement = new Statement( $badSnak );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$cases['invalid value in main snak'] = array( $q17, $statement );

		$statement = new Statement( $brokenSnak );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$cases['mismatching value in main snak'] = array( $q17, $statement );


		$statement = new Statement( $goodSnak );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$statement->setQualifiers( new SnakList( array( $badSnak ) ) );
		$cases['bad snak in qualifiers'] = array( $q17, $statement );

		$statement = new Statement( $goodSnak );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$statement->setQualifiers( new SnakList( array( $brokenSnak ) ) );
		$cases['mismatching value in qualifier'] = array( $q17, $statement );


		$statement = new Statement( $goodSnak );
		$reference = new Reference( new SnakList( array( $badSnak ) ) );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$statement->setReferences( new ReferenceList( array( $reference ) ) );
		$cases['bad snak in reference'] = array( $q17, $statement );

		$statement = new Statement( $goodSnak );
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
		$changeOpClaim = new ChangeOpClaim(
			$statement,
			new ClaimGuidGenerator(),
			$this->mockProvider->getMockGuidValidator(),
			$this->mockProvider->getMockGuidParser( $entityId ),
			$this->mockProvider->getMockSnakValidator(),
			0
		);

		$entity = Item::newEmpty();
		$entity->setId( $entityId );

		$result = $changeOpClaim->validate( $entity );
		$this->assertFalse( $result->isValid(), 'isValid()' );
	}

}
