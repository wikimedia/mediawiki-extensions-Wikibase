<?php

namespace Wikibase\Test\Api;

use DataValues\StringValue;
use FormatJson;
use Revision;
use UsageException;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Claim\Statement;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Lib\ClaimGuidGenerator;

/**
 * @covers Wikibase\Api\SetClaim
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group SetClaimTest
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Adam Shorland
 */
class SetClaimTest extends WikibaseApiTestCase {

	private static $propertyIds;

	protected function setUp() {
		parent::setUp();

		if ( !self::$propertyIds ) {
			self::$propertyIds = $this->getPropertyIds();
		}
	}

	private function getPropertyIds() {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$propertyIds = array();

		for( $i = 0; $i < 4; $i++ ) {
			$property = Property::newEmpty();
			$property->setDataTypeId( 'string' );

			$store->saveEntity( $property, 'testing', $GLOBALS['wgUser'], EDIT_NEW );

			$propertyIds[] = $property->getId();
		}

		return $propertyIds;
	}

	/**
	 * @return Snak[]
	 */
	private function getSnaks() {
		$snaks = array();

		$snaks[] = new PropertyNoValueSnak( self::$propertyIds[0] );
		$snaks[] = new PropertySomeValueSnak( self::$propertyIds[1] );
		$snaks[] = new PropertyValueSnak( self::$propertyIds[2], new StringValue( 'o_O' ) );

		return $snaks;
	}

	private function getClaims() {
		$claims = array();

		$ranks = array(
			Statement::RANK_DEPRECATED,
			Statement::RANK_NORMAL,
			Statement::RANK_PREFERRED
		);

		$snaks = $this->getSnaks();
		$snakList = new SnakList( $snaks );
		$mainSnak = $snaks[0];
		$statement = new Statement( $mainSnak );
		$statement->setRank( $ranks[array_rand( $ranks )] );
		$claims[] = $statement;

		foreach ( $snaks as $snak ) {
			$statement = clone $statement;
			$statement->getReferences()->addReference( new Reference( new SnakList( $snak ) ) );
			$statement->setRank( $ranks[array_rand( $ranks )] );
			$claims[] = $statement;
		}

		$statement = clone $statement;

		$statement->getReferences()->addReference( new Reference( $snakList ) );
		$statement->setRank( $ranks[array_rand( $ranks )] );
		$claims[] = $statement;

		$statement = clone $statement;
		$statement->setQualifiers( $snakList );
		$statement->getReferences()->addReference( new Reference( $snakList ) );
		$statement->setRank( $ranks[array_rand( $ranks )] );
		$claims[] = $statement;

		return $claims;
	}

	public function testAddClaim() {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$claims = $this->getClaims();

		/** @var Claim[] $claims */
		foreach( $claims as $claim ) {
			$item = Item::newEmpty();
			$store->saveEntity( $item, 'setclaimtest', $GLOBALS['wgUser'], EDIT_NEW );
			$itemId = $item->getId();

			$guidGenerator = new ClaimGuidGenerator();
			$guid = $guidGenerator->newGuid( $itemId );

			$claim->setGuid( $guid );

			// Addition request
			$this->makeRequest( $claim, $itemId, 1, 'addition request' );

			// Reorder qualifiers
			if( count( $claim->getQualifiers() ) > 0 ) {
				// Simply reorder the qualifiers by putting the first qualifier to the end. This is
				// supposed to be done in the serialized representation since changing the actual
				// object might apply intrinsic sorting.
				$serializerFactory = new SerializerFactory();
				$serializer = $serializerFactory->newSerializerForObject( $claim );
				$serializedClaim = $serializer->getSerialized( $claim );
				$firstPropertyId = array_shift( $serializedClaim['qualifiers-order'] );
				array_push( $serializedClaim['qualifiers-order'], $firstPropertyId );
				$this->makeRequest( $serializedClaim, $itemId, 1, 'reorder qualifiers' );
			}

			$claim = new Statement( new PropertyNoValueSnak( self::$propertyIds[1] ) );
			$claim->setGuid( $guid );

			// Update request
			$this->makeRequest( $claim, $itemId, 1, 'update request' );
		}
	}

	public function getInvalidCases() {
		...store these...

		$p11 = new PropertyId( 'P11' );
		$q17 = new ItemId( 'Q17' );
		$q9 = new ItemId( 'Q9' );

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
		$cases['bad claim in qualifiers'] = array( $q9, $claim );

		$claim = new Claim( $goodSnak );
		$claim->setGuid( $guidGenerator->newGuid( $q17 ) );
		$claim->setQualifiers( new SnakList( array( $brokenSnak ) ) );
		$cases['mismatching value in qualifier'] = array( $q9, $claim );


		$claim = new Statement( $goodSnak );
		$reference = new Reference( new SnakList( array( $badSnak ) ) );
		$claim->setGuid( $guidGenerator->newGuid( $q17 ) );
		$claim->setReferences( new ReferenceList( array( $reference ) ) );
		$cases['bad claim in reference'] = array( $q9, $claim );


		$claim = new Statement( $goodSnak );
		$reference = new Reference( new SnakList( array( $badSnak ) ) );
		$claim->setGuid( $guidGenerator->newGuid( $q17 ) );
		$claim->setReferences( new ReferenceList( array( $reference ) ) );
		$cases['mismatching value in reference'] = array( $q9, $claim );

		return $cases;
	}

	public function testAddInvalidClaim() {
		$cases = $this->getInvalidCases();

		/** @var Claim $claim */
		/** @var ItemId $itemId */
		foreach( $cases as $case ) {
			list( $itemId, $claim, $error ) = $case;

			$this->makeRequest( $claim, $itemId, 1, 'invalid update request', null, null, $error );
		}
	}

	public function testSetClaimAtIndex() {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		// Generate an item with some claims:
		$item = Item::newEmpty();
		$claims = new Claims();

		// Initialize item content with empty claims:
		$item->setClaims( $claims );
		$store->saveEntity( $item, 'setclaimtest', $GLOBALS['wgUser'], EDIT_NEW );
		$itemId = $item->getId();

		$guidGenerator = new ClaimGuidGenerator();

		for( $i = 1; $i <= 3; $i++ ) {
			$preexistingClaim = $item->newClaim( new PropertyNoValueSnak( $i ) );
			$preexistingClaim->setGuid( $guidGenerator->newGuid( $itemId ) );
			$claims->addClaim( $preexistingClaim );
		}

		// Add preexisting claims:
		$item->setClaims( $claims );
		$store->saveEntity( $item, 'setclaimtest', $GLOBALS['wgUser'], EDIT_UPDATE );

		// Add new claim at index 2:
		$guid = $guidGenerator->newGuid( $itemId );
		/** @var Claim $claim */
		foreach( $this->getClaims() as $claim ) {
			$claim->setGuid( $guid );

			$this->makeRequest( $claim, $itemId, 4, 'addition request', 2 );
		}
	}

	/**
	 * @param Claim|array $claim Native or serialized claim object.
	 * @param EntityId $entityId
	 * @param $claimCount
	 * @param $requestLabel string a label to identify requests that are made in errors
	 * @param int|null $index
	 * @param int|null $baserevid
	 * @param string $error
	 */
	protected function makeRequest(
		$claim,
		EntityId $entityId,
		$claimCount,
		$requestLabel,
		$index = null,
		$baserevid = null,
		$error = null
	) {
		$serializerFactory = new SerializerFactory();

		if( is_a( $claim, '\Wikibase\Claim' ) ) {
			$unserializer = $serializerFactory->newSerializerForObject( $claim );
			$serializedClaim = $unserializer->getSerialized( $claim );
		} else {
			$unserializer = $serializerFactory->newUnserializerForClass( 'Wikibase\Claim' );
			$serializedClaim = $claim;
			$claim = $unserializer->newFromSerialization( $serializedClaim );
		}

		$params = array(
			'action' => 'wbsetclaim',
			'claim' => FormatJson::encode( $serializedClaim ),
		);

		if( !is_null( $index ) ) {
			$params['index'] = $index;
		}

		if( !is_null( $baserevid ) ) {
			$params['baserevid'] = $baserevid;
		}

		$resultArray = $this->assertApiRequest( $params, $error );

		$this->assertValidResponse( $resultArray );
		$this->assertClaimWasSet( $claim, $entityId, $claimCount, $requestLabel );
	}

	/**
	 * @param array $params
	 * @param string|null $error
	 *
	 * @return array|bool
	 */
	protected function assertApiRequest( $params, $error ) {
		$resultArray = false;

		try {
			list( $resultArray, ) = $this->doApiRequestWithToken( $params );

			if ( $error !== null ) {
				$this->fail( "Did not cause expected error: $error" );
			}
		} catch ( UsageException $ex ) {
			if ( $error ) {
				$this->assertEquals( $error, $ex->getCodeString(), 'expected error' );
			} else {
				$this->fail( "Caused unexpected error!" . $ex );
			}
		}

		return $resultArray;
	}

	protected function assertValidResponse( array $resultArray ) {
		$this->assertResultSuccess( $resultArray );
		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'pageinfo', $resultArray, 'top level element has a pageinfo key' );
		$this->assertArrayHasKey( 'claim', $resultArray, 'top level element has a statement key' );

		if( isset( $resultArray['claim']['qualifiers'] ) ) {
			$this->assertArrayHasKey( 'qualifiers-order', $resultArray['claim'], '"qualifiers-order" key is set when returning qualifiers' );
		}
	}

	/**
	 * @param Claim $claim
	 * @param EntityId $entityId
	 * @param $claimCount
	 * @param $requestLabel string a label to identify requests that are made in errors
	 */
	protected function assertClaimWasSet(
		Claim $claim,
		EntityId $entityId,
		$claimCount,
		$requestLabel
	) {
		$item = WikibaseRepo::getDefaultInstance()->getEntityLookup()->getEntity( $entityId );

		$claims = new Claims( $item->getClaims() );
		$this->assertTrue( $claims->hasClaim( $claim ), "Claims list does not have claim after {$requestLabel}" );

		$savedClaim = $claims->getClaimWithGuid( $claim->getGuid() );
		if( count( $claim->getQualifiers() ) ) {
			$this->assertArrayEquals( $claim->getQualifiers()->toArray(), $savedClaim->getQualifiers()->toArray(), true );
		}

		$this->assertEquals( $claimCount, $claims->count(), "Claims count is wrong after {$requestLabel}" );
	}

	/**
	 * @see Bug 58394 - "specified index out of bounds" issue when moving a statement
	 * @note A hack is  in place in ChangeOpClaim to allow this
	 */
	public function testBug58394SpecifiedIndexOutOfBounds() {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		// Initialize item content with empty claims:
		$item = Item::newEmpty();
		$claims = new Claims();
		$item->setClaims( $claims );
		$store->saveEntity( $item, 'setclaimtest', $GLOBALS['wgUser'], EDIT_NEW );

		// Generate a single claim:
		$itemId = $item->getId();
		$guidGenerator = new ClaimGuidGenerator();
		$preexistingClaim = $item->newClaim( new PropertyNoValueSnak( self::$propertyIds[1] ) );
		$preexistingClaim->setGuid( $guidGenerator->newGuid( $itemId ) );
		$claims->addClaim( $preexistingClaim );

		// Save the single claim
		$item->setClaims( $claims );
		$revision = $store->saveEntity( $item, 'setclaimtest', $GLOBALS['wgUser'], EDIT_UPDATE );

		// Add new claim at index 3 using the baserevid and a different property id
		$newClaim = $item->newClaim( new PropertyNoValueSnak( self::$propertyIds[2] ) );
		$newClaim->setGuid( $guidGenerator->newGuid( $itemId ) );
		$this->makeRequest( $newClaim, $itemId, 2, 'addition request', 3, $revision->getRevision() );
	}

}
