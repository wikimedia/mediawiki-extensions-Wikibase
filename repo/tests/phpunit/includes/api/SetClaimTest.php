<?php

namespace Wikibase\Test\Api;

use FormatJson;
use Wikibase\Claim;
use Wikibase\Claims;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Property;
use Wikibase\PropertyContent;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Statement;
use Wikibase\Reference;
use Wikibase\Snak;
use Wikibase\SnakList;
use Wikibase\PropertyValueSnak;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\Item;
use Wikibase\ItemContent;
use Wikibase\Lib\ClaimGuidGenerator;

/**
 * @covers Wikibase\Api\SetClaim
 *
 * @since 0.4
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

	public function setUp() {
		parent::setUp();

		static $hasProperties = false;

		if ( !$hasProperties ) {
			// Create the properties once
			$propertyIds = self::getPropertyIds();

			foreach( $propertyIds as $propertyId ) {
				$prop = PropertyContent::newEmpty();
				$prop->getEntity()->setId( $propertyId );
				$prop->getEntity()->setDataTypeId( 'string' );
				$prop->save( 'testing' );
			}

			$hasProperties = true;
		}
	}

	/**
	 * @return PropertyId[]
	 */
	protected static function getPropertyIds() {
		return array(
			new PropertyId( 'P42' ),
			new PropertyId( 'P9001' ),
			new PropertyId( 'P7201010' )
		);
	}

	/**
	 * @return Snak[]
	 */
	protected static function snakProvider() {
		$ropertyIds = self::getPropertyIds();

		$snaks = array();

		$snaks[] = new PropertyNoValueSnak( $ropertyIds[0] );
		$snaks[] = new PropertySomeValueSnak( $ropertyIds[1] );
		$snaks[] = new PropertyValueSnak( $ropertyIds[2], new \DataValues\StringValue( 'o_O' ) );

		return $snaks;
	}

	public static function provideClaims() {
		$testCases = array();

		$ranks = array(
			Statement::RANK_DEPRECATED,
			Statement::RANK_NORMAL,
			Statement::RANK_PREFERRED
		);

		$snaks = self::snakProvider();
		$mainSnak = $snaks[0];
		$statement = new Statement( $mainSnak );
		$statement->setRank( $ranks[array_rand( $ranks )] );
		$testCases[] = array( $statement );

		foreach ( $snaks as $snak ) {
			$statement = clone $statement;
			$snaks = new SnakList( array( $snak ) );
			$statement->getReferences()->addReference( new Reference( $snaks ) );
			$statement->setRank( $ranks[array_rand( $ranks )] );
			$testCases[] = array( $statement );
		}

		$statement = clone $statement;
		$snaks = new SnakList( self::snakProvider() );
		$statement->getReferences()->addReference( new Reference( $snaks ) );
		$statement->setRank( $ranks[array_rand( $ranks )] );
		$testCases[] = array( $statement );

		$statement = clone $statement;
		$snaks = new SnakList( self::snakProvider() );
		$statement->setQualifiers( $snaks );
		$statement->getReferences()->addReference( new Reference( $snaks ) );
		$statement->setRank( $ranks[array_rand( $ranks )] );
		$testCases[] = array( $statement );

		return $testCases;
	}

	/**
	 * @dataProvider provideClaims
	 */
	public function testAddClaim( Claim $claim ) {
		$item = Item::newEmpty();
		$content = new ItemContent( $item );
		$content->save( 'setclaimtest', null, EDIT_NEW );

		$guidGenerator = new ClaimGuidGenerator( $item->getId() );
		$guid = $guidGenerator->newGuid();

		$claim->setGuid( $guid );

		// Addition request
		$this->makeRequest( $claim, $item->getId(), 1, 'addition request' );

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
			$this->makeRequest( $serializedClaim, $item->getId(), 1, 'reorder qualifiers' );
		}

		$claim = new Statement( new PropertyNoValueSnak( 9001 ) );
		$claim->setGuid( $guid );

		// Update request
		$this->makeRequest( $claim, $item->getId(), 1, 'update request' );
	}

	/**
	 * @dataProvider provideClaims
	 */
	public function testSetClaimAtIndex( Claim $claim ) {
		// Generate an item with some claims:
		$item = Item::newEmpty();
		$item->setId( ItemId::newFromNumber( 906054 ) );
		$guidGenerator = new ClaimGuidGenerator( $item->getId() );

		$claims = new Claims();

		// (Re-)initialize item content with empty claims:
		$item->setClaims( $claims );
		$content = new ItemContent( $item );
		$content->save( 'setclaimtest', null, EDIT_NEW );

		for( $i = 1; $i <= 3; $i++ ) {
			$preexistingClaim = $item->newClaim( new PropertyNoValueSnak( $i ) );
			$preexistingClaim->setGuid( $guidGenerator->newGuid() );
			$claims->addClaim( $preexistingClaim );
		}

		// Add preexisting claims:
		$item->setClaims( $claims );
		$content = new ItemContent( $item );
		$content->save( 'setclaimtest', null, EDIT_UPDATE );

		// Add new claim at index 2:
		$guid = $guidGenerator->newGuid();
		$claim->setGuid( $guid );

		$this->makeRequest( $claim, $item->getId(), 4, 'addition request', 2 );
	}

	/**
	 * @param Claim|array $claim Native or serialized claim object.
	 * @param EntityId $entityId
	 * @param $claimCount
	 * @param $requestLabel string a label to identify requests that are made in errors
	 * @param int|null $index
	 */
	protected function makeRequest(
		$claim,
		EntityId $entityId,
		$claimCount,
		$requestLabel,
		$index = null
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

		$this->makeValidRequest( $params );

		$content = WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->getFromId( $entityId );
		$this->assertInstanceOf( '\Wikibase\EntityContent', $content );

		$claims = new Claims( $content->getEntity()->getClaims() );
		$this->assertTrue( $claims->hasClaim( $claim ), "Claims list does not have claim after {$requestLabel}" );

		$savedClaim = $claims->getClaimWithGuid( $claim->getGuid() );
		if( count( $claim->getQualifiers() ) ) {
			$this->assertArrayEquals( $claim->getQualifiers()->toArray(), $savedClaim->getQualifiers()->toArray(), true );
		}

		$this->assertEquals( $claimCount, $claims->count(), "Claims count is wrong after {$requestLabel}" );
	}

	protected function makeValidRequest( array $params ) {
		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertResultSuccess( $resultArray );
		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'pageinfo', $resultArray, 'top level element has a pageinfo key' );
		$this->assertArrayHasKey( 'claim', $resultArray, 'top level element has a statement key' );

		if( isset( $resultArray['claim']['qualifiers'] ) ) {
			$this->assertArrayHasKey( 'qualifiers-order', $resultArray['claim'], '"qualifiers-order" key is set when returning qualifiers' );
		}

		return $resultArray;
	}

}
