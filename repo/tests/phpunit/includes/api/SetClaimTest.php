<?php

namespace Wikibase\Test\Api;

use DataValues\NumberValue;
use DataValues\StringValue;
use FormatJson;
use UsageException;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Repo\WikibaseRepo;

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

		for ( $i = 0; $i < 4; $i++ ) {
			$property = Property::newFromType( 'string' );

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

	/**
	 * @return Statement[]
	 */
	private function getStatements() {
		$statements = array();

		$ranks = array(
			Statement::RANK_DEPRECATED,
			Statement::RANK_NORMAL,
			Statement::RANK_PREFERRED
		);

		$snaks = $this->getSnaks();
		$snakList = new SnakList( $snaks );
		$mainSnak = $snaks[0];
		$statement = new Statement( new Claim( $mainSnak ) );
		$statement->setRank( $ranks[array_rand( $ranks )] );
		$statements[] = $statement;

		foreach ( $snaks as $snak ) {
			$statement = unserialize( serialize( $statement ) );
			$statement->getReferences()->addReference( new Reference( new SnakList( $snak ) ) );
			$statement->setRank( $ranks[array_rand( $ranks )] );
			$statements[] = $statement;
		}

		$statement = unserialize( serialize( $statement ) );

		$statement->getReferences()->addReference( new Reference( $snakList ) );
		$statement->setRank( $ranks[array_rand( $ranks )] );
		$statements[] = $statement;

		$statement = unserialize( serialize( $statement ) );
		$statement->setQualifiers( $snakList );
		$statement->getReferences()->addReference( new Reference( $snakList ) );
		$statement->setRank( $ranks[array_rand( $ranks )] );
		$statements[] = $statement;

		return $statements;
	}

	public function testAddClaim() {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$statements = $this->getStatements();

		foreach ( $statements as $statement ) {
			$item = Item::newEmpty();
			$store->saveEntity( $item, 'setclaimtest', $GLOBALS['wgUser'], EDIT_NEW );
			$itemId = $item->getId();

			$guidGenerator = new ClaimGuidGenerator();
			$guid = $guidGenerator->newGuid( $itemId );

			$statement->setGuid( $guid );

			// Addition request
			$this->makeRequest( $statement, $itemId, 1, 'addition request' );

			// Reorder qualifiers
			if( count( $statement->getQualifiers() ) > 0 ) {
				// Simply reorder the qualifiers by putting the first qualifier to the end. This is
				// supposed to be done in the serialized representation since changing the actual
				// object might apply intrinsic sorting.
				$serializerFactory = new SerializerFactory();
				$serializer = $serializerFactory->newSerializerForObject( $statement );
				$serializedClaim = $serializer->getSerialized( $statement );
				$firstPropertyId = array_shift( $serializedClaim['qualifiers-order'] );
				array_push( $serializedClaim['qualifiers-order'], $firstPropertyId );
				$this->makeRequest( $serializedClaim, $itemId, 1, 'reorder qualifiers' );
			}

			$newSnak = new PropertyValueSnak( $statement->getPropertyId(), new StringValue( '\o/' ) );
			$newClaim = new Statement( new Claim( $newSnak ) );
			$newClaim->setGuid( $guid );

			// Update request
			$this->makeRequest( $statement, $itemId, 1, 'update request' );
		}
	}

	public function getInvalidCases() {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$item = Item::newEmpty();
		$store->saveEntity( $item, 'setclaimtest', $GLOBALS['wgUser'], EDIT_NEW );
		$q17 = $item->getId();

		$property = Property::newFromType( 'string' );
		$store->saveEntity( $property, 'setclaimtest', $GLOBALS['wgUser'], EDIT_NEW );
		$p11 = $property->getId();

		$item = Item::newEmpty();
		$store->saveEntity( $item, 'setclaimtest', $GLOBALS['wgUser'], EDIT_NEW );
		$qx = $item->getId();
		$store->deleteEntity( $qx, 'setclaimtest', $GLOBALS['wgUser'] );

		$property = Property::newFromType( 'string' );
		$store->saveEntity( $property, 'setclaimtest', $GLOBALS['wgUser'], EDIT_NEW );
		$px = $property->getId();
		$store->deleteEntity( $px, 'setclaimtest', $GLOBALS['wgUser'] );

		$goodSnak = new PropertyValueSnak( $p11, new StringValue( 'good' ) );
		$badSnak = new PropertyValueSnak( $p11, new StringValue( ' x ' ) );
		$brokenSnak = new PropertyValueSnak( $p11, new NumberValue( 23 ) );
		$obsoleteSnak = new PropertyValueSnak( $px, new StringValue( ' x ' ) );

		$guidGenerator = new ClaimGuidGenerator();

		$cases = array();

		$claim = new Statement( new Claim( $badSnak ) );
		$claim->setGuid( $guidGenerator->newGuid( $q17 ) );
		$cases['invalid value in main snak'] = array( $q17, $claim, 'modification-failed' );

		$claim = new Statement( new Claim( $brokenSnak ) );
		$claim->setGuid( $guidGenerator->newGuid( $q17 ) );
		$cases['mismatching value in main snak'] = array( $q17, $claim, 'modification-failed' );

		$claim = new Statement( new Claim( $obsoleteSnak ) );
		$claim->setGuid( $guidGenerator->newGuid( $q17 ) );
		$cases['obsolete snak using deleted property'] = array( $q17, $claim, 'modification-failed' );

		$claim = new Statement( new Claim( $goodSnak ) );
		$claim->setGuid( $guidGenerator->newGuid( $qx ) );
		$cases['good claim for deleted item'] = array( $qx, $claim, 'cant-load-entity-content' );

		$claim = new Statement( new Claim( $goodSnak ) );
		$claim->setGuid( $guidGenerator->newGuid( $q17 ) );
		$claim->setQualifiers( new SnakList( array( $badSnak ) ) );
		$cases['bad snak in qualifiers'] = array( $q17, $claim, 'modification-failed' );

		$claim = new Statement( new Claim( $goodSnak ) );
		$claim->setGuid( $guidGenerator->newGuid( $q17 ) );
		$claim->setQualifiers( new SnakList( array( $brokenSnak ) ) );
		$cases['mismatching value in qualifier'] = array( $q17, $claim, 'modification-failed' );

		$claim = new Statement( new Claim( $goodSnak ) );
		$reference = new Reference( new SnakList( array( $badSnak ) ) );
		$claim->setGuid( $guidGenerator->newGuid( $q17 ) );
		$claim->setReferences( new ReferenceList( array( $reference ) ) );
		$cases['bad snak in reference'] = array( $q17, $claim, 'modification-failed' );

		$claim = new Statement( new Claim( $goodSnak ) );
		$reference = new Reference( new SnakList( array( $badSnak ) ) );
		$claim->setGuid( $guidGenerator->newGuid( $q17 ) );
		$claim->setReferences( new ReferenceList( array( $reference ) ) );
		$cases['mismatching value in reference'] = array( $q17, $claim, 'modification-failed' );

		return $cases;
	}

	public function testAddInvalidClaim() {
		$cases = $this->getInvalidCases();

		foreach ( $cases as $label => $case ) {
			list( $itemId, $statement, $error ) = $case;

			$this->makeRequest( $statement, $itemId, 1, $label, null, null, $error );
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

		for ( $i = 1; $i <= 3; $i++ ) {
			$preexistingClaim = $item->newClaim( new PropertyNoValueSnak( $i ) );
			$preexistingClaim->setGuid( $guidGenerator->newGuid( $itemId ) );
			$claims->addClaim( $preexistingClaim );
		}

		// Add preexisting claims:
		$item->setClaims( $claims );
		$store->saveEntity( $item, 'setclaimtest', $GLOBALS['wgUser'], EDIT_UPDATE );

		$guid = $guidGenerator->newGuid( $itemId );
		foreach ( $this->getStatements() as $statement ) {
			$statement->setGuid( $guid );

			// Add new claim at index 2:
			$this->makeRequest( $statement, $itemId, 4, 'addition request', 2 );
		}
	}

	/**
	 * @param Claim|array $claim Native or serialized claim object.
	 * @param ItemId $itemId
	 * @param $claimCount
	 * @param $requestLabel string a label to identify requests that are made in errors
	 * @param int|null $index
	 * @param int|null $baserevid
	 * @param string $error
	 */
	protected function makeRequest(
		$claim,
		ItemId $itemId,
		$claimCount,
		$requestLabel,
		$index = null,
		$baserevid = null,
		$error = null
	) {
		$serializerFactory = new SerializerFactory();

		if ( $claim instanceof Claim ) {
			$serializer = $serializerFactory->newSerializerForObject( $claim );
			$serializedClaim = $serializer->getSerialized( $claim );
		} else {
			$unserializer = $serializerFactory->newUnserializerForClass( 'Wikibase\DataModel\Claim\Claim' );
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

		if ( $resultArray ) {
			$this->assertValidResponse( $resultArray );
			$this->assertClaimWasSet( $claim, $itemId, $claimCount, $requestLabel );
		}
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
				$this->fail( "Did not cause expected error $error" );
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
			$this->assertTrue( $claim->getQualifiers()->equals( $savedClaim->getQualifiers() ) );
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

	public function testBadPropertyError() {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$serializerFactory = new SerializerFactory();

		// create property
		$property = Property::newFromType( 'quantity' );
		$property = $store->saveEntity( $property, '', $GLOBALS['wgUser'], EDIT_NEW )->getEntity();

		// create item
		$item = Item::newEmpty();
		$item = $store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_NEW )->getEntity();

		// add a claim
		$guidGenerator = new ClaimGuidGenerator();
		$claim = new Statement( new Claim( new PropertyNoValueSnak( $property->getId() ) ) );
		$claim->setGuid( $guidGenerator->newGuid( $item->getId() ) );

		$item->addClaim( $claim );
		$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_UPDATE )->getEntity();

		// try to change the main snak's property
		$badProperty = Property::newFromType( 'string' );
		$badProperty = $store->saveEntity( $badProperty, '', $GLOBALS['wgUser'], EDIT_NEW )->getEntity();

		$badClaim = new Statement( new Claim( new PropertyNoValueSnak( $badProperty->getId() ) ) );

		$serializer = $serializerFactory->newSerializerForObject( $claim );
		$serializedBadClaim = $serializer->getSerialized( $badClaim );

		$params = array(
			'action' => 'wbsetclaim',
			'claim' => \FormatJson::encode( $serializedBadClaim ),
		);

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Changed main snak property did not raise an error' );
		} catch ( UsageException $e ) {
			$this->assertEquals( 'invalid-claim', $e->getCodeString(), 'Changed main snak property' );
		}
	}

}
