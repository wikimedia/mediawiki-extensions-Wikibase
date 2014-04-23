<?php

namespace Wikibase\Test;

use DataValues\NumberValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOpClaim;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\ClaimGuidParser;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Claim\Statement;
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
		$item777 = self::makeNewItemWithClaim( 'Q777', new PropertyNoValueSnak( 45 ) );
		$item666 = self::makeNewItemWithClaim( 'Q666', new PropertySomeValueSnak( 44 ) );

		$item777Claims = $item777->getClaims();
		$item666Claims = $item666->getClaims();

		$claim777 = reset( $item777Claims );
		$claim666 = reset( $item666Claims );

		//claims that exist on the given entities
		$claims[0] = new Claim( new PropertyNoValueSnak( 43 ) );
		$claims[777] = clone $claim777;
		$claims[666] = clone $claim666;
		//claims with a null guid
		$claims[7770] = clone $claim777;
		$claims[7770]->setGuid( null );
		$claims[6660] = clone $claim666;
		$claims[6660]->setGuid( null );
		//new claims not yet on the entity
		$claims[7777] = clone $claim777;
		$claims[7777]->setGuid( 'Q777$D8404CDA-25E4-4334-AF13-A3290BC77777' );
		$claims[6666] = clone $claim666;
		$claims[6666]->setGuid( 'Q666$D8404CDA-25E4-4334-AF13-A3290BC66666' );

		$claims[11] = new Claim( new PropertyNoValueSnak( 1 ) );
		$claims[11]->setGuid( null );
		$claims[12] = new Claim( new PropertySomeValueSnak( 1 ) );
		$claims[12]->setGuid( null );
		$claims[13] = clone $claims[12];
		$claims[13]->setGuid( 'Q666$D8404CDA-25E4-4334-AF13-A3290BC66613' );

		$args = array();
		//test adding claims with guids from other items(these shouldn't be added)
		$args[] = array( $itemEmpty, $claims[666], false );
		$args[] = array( $itemEmpty, $claims[777], false );
		$args[] = array( $item666, $claims[777], false );
		$args[] = array( $item777, $claims[666], false );
		//test adding the same claims with a null guid (a guid should be created)
		$args[] = array( $item777, $claims[7770], array( $claims[777], $claims[7770] ) );
		$args[] = array( $item666, $claims[6660], array( $claims[666], $claims[6660] ) );
		//test adding the same claims with a correct but different guid (these should be added)
		$args[] = array( $item777, $claims[7777], array( $claims[777], $claims[7770], $claims[7777] ) );
		$args[] = array( $item666, $claims[6666], array( $claims[666], $claims[6660], $claims[6666] ) );
		//test adding the same claims with and id that already exists (these shouldn't be added)
		$args[] = array( $item777, $claims[7777], array( $claims[777], $claims[7770], $claims[7777] ) );
		$args[] = array( $item666, $claims[6666], array( $claims[666], $claims[6660], $claims[6666] ) );
		// test adding a claim at a specific index
		$args[] = array( $item777, $claims[0], array( $claims[0], $claims[777], $claims[7770], $claims[7777] ), 0 );
		// test moving a claim
		$args[] = array( $item666, $claims[6666], array( $claims[666], $claims[6666], $claims[6660] ), 1 );
		// test adding a claim featuring another property id within the boundaries of claims the
		// same property
		$args[] = array( $item666, $claims[11], array( $claims[666], $claims[6666], $claims[6660], $claims[11] ), 1 );
		// test moving a subset of claims featuring the same property
		$args[] = array( $item666, $claims[12], array( $claims[12], $claims[11], $claims[666], $claims[6666], $claims[6660] ), 0 );

		return $args;
	}

	/**
	 * @dataProvider provideTestApply
	 *
	 * @param Entity $entity
	 * @param Claim $claim
	 * @param Claim[]|bool $expected
	 * @param int|null $index
	 */
	public function testApply( $entity, $claim, $expected, $index = null ) {
		if ( $expected === false ) {
			$this->setExpectedException( '\Wikibase\ChangeOp\ChangeOpException' );
		}

		$idParser = new BasicEntityIdParser();
		$changeOpClaim = new ChangeOpClaim(
			$claim,
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

		foreach ( $expected as $expectedClaim ) {
			$guid = $expectedClaim->getGuid();
			$hash = $expectedClaim->getHash();

			if ( $guid !== null ) {
				$this->assertEquals( $i++, $entityClaims->indexOf( $expectedClaim ) );
			}

			$this->assertArrayHasKey( $hash, $entityClaimHashSet );
		}

		$this->assertEquals( count( $expected ), $entityClaims->count() );
	}

	public function provideInvalidApply() {
		/* @var Claim $claim */

		$snak = new PropertyNoValueSnak( 67573284 );
		$item = $this->makeNewItemWithClaim( 'Q777', $snak );
		$claims = $item->getClaims();
		$claim = reset( $claims );

		// change main snak to "some value"
		$newSnak = new PropertySomeValueSnak( 67573284 );
		$newClaim = clone $claim;
		$newClaim->setMainSnak( $newSnak );

		// apply change to the wrong item
		$wrongItem = Item::newEmpty();
		$wrongItem->setId( new ItemId( "Q888" ) );
		$args['wrong entity'] = array ( $wrongItem, new ChangeOpClaim(
			$newClaim,
			$this->mockProvider->getGuidGenerator(),
			$this->mockProvider->getMockGuidValidator(),
			$this->mockProvider->getMockGuidParser( $item->getId() ),
			$this->mockProvider->getMockSnakValidator()
		) );

		//TODO: once we stop allowing user-generated GUIDs for new claims, test this below.
		// apply change to an unknown claim
		/*
		$wrongClaimId = $item->getId()->getPrefixedId() . '$DEADBEEF-DEAD-BEEF-DEAD-BEEFDEADBEEF';
		$badClaim = clone $newClaim;
		$badClaim->setGuid( $wrongClaimId );
		$args['unknown claim'] = array ( $item, new ChangeOpClaim( $badClaim, $guidGenerator ) );
		*/

		// update an existing claim with wrong main snak property
		$newSnak = new PropertyNoValueSnak( 23452345 );
		$newClaim->setMainSnak( $newSnak );

		$changeOp =  new ChangeOpClaim(
			$newClaim,
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
	protected function makeNewItemWithClaim( $itemId, $snak ) {
		$entity = Item::newEmpty();
		$entity->setId( new ItemId( $itemId ) );

		$claim = $entity->newClaim( $snak );
		$guidGenerator = new ClaimGuidGenerator();
		$claim->setGuid( $guidGenerator->newGuid( $entity->getId() ) );

		$claims = new Claims();
		$claims->addClaim( $claim );
		$entity->setClaims( $claims );

		return $entity;
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

		$claim = new Claim( $badSnak );
		$claim->setGuid( $guidGenerator->newGuid( $q17 ) );
		$cases['invalid value in main snak'] = array( $q17, $claim );

		$claim = new Claim( $brokenSnak );
		$claim->setGuid( $guidGenerator->newGuid( $q17 ) );
		$cases['mismatching value in main snak'] = array( $q17, $claim );


		$claim = new Claim( $goodSnak );
		$claim->setGuid( $guidGenerator->newGuid( $q17 ) );
		$claim->setQualifiers( new SnakList( array( $badSnak ) ) );
		$cases['bad snak in qualifiers'] = array( $q17, $claim );

		$claim = new Claim( $goodSnak );
		$claim->setGuid( $guidGenerator->newGuid( $q17 ) );
		$claim->setQualifiers( new SnakList( array( $brokenSnak ) ) );
		$cases['mismatching value in qualifier'] = array( $q17, $claim );


		$claim = new Statement( $goodSnak );
		$reference = new Reference( new SnakList( array( $badSnak ) ) );
		$claim->setGuid( $guidGenerator->newGuid( $q17 ) );
		$claim->setReferences( new ReferenceList( array( $reference ) ) );
		$cases['bad snak in reference'] = array( $q17, $claim );

		$claim = new Statement( $goodSnak );
		$reference = new Reference( new SnakList( array( $badSnak ) ) );
		$claim->setGuid( $guidGenerator->newGuid( $q17 ) );
		$claim->setReferences( new ReferenceList( array( $reference ) ) );
		$cases['mismatching value in reference'] = array( $q17, $claim );

		return $cases;
	}

	/**
	 * @dataProvider validateProvider
	 */
	public function testValidate( EntityId $entityId, Claim $claim ) {
		$changeOpClaim = new ChangeOpClaim(
			$claim,
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
