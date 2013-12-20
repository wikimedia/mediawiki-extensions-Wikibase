<?php

namespace Wikibase\Test\Api;

use DataValues\StringValue;
use FormatJson;
use Wikibase\Claim;
use Wikibase\Claims;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\EntityId;
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

	private static $propertyIds;

	protected function setUp() {
		parent::setUp();

		if ( !self::$propertyIds ) {
			self::$propertyIds = $this->getPropertyIds();
		}
	}

	private function getPropertyIds() {
		$propertyIds = array();

		for( $i = 0; $i < 4; $i++ ) {
			$propertyContent = PropertyContent::newEmpty();
			$propertyContent->getProperty()->setDataTypeId( 'string' );
			$propertyContent->save( 'testing', null, EDIT_NEW );

			$propertyIds[] = $propertyContent->getProperty()->getId();
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
		$claims = $this->getClaims();

		foreach( $claims as $claim ) {
			$item = Item::newEmpty();
			$content = new ItemContent( $item );
			$content->save( 'setclaimtest', null, EDIT_NEW );
			$itemId = $content->getItem()->getId();

			$guidGenerator = new ClaimGuidGenerator( $itemId );
			$guid = $guidGenerator->newGuid();

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

	public function testSetClaimAtIndex() {
		// Generate an item with some claims:
		$item = Item::newEmpty();
		$claims = new Claims();

		// Initialize item content with empty claims:
		$item->setClaims( $claims );
		$content = new ItemContent( $item );
		$content->save( 'setclaimtest', null, EDIT_NEW );
		$itemId = $content->getItem()->getId();

		$guidGenerator = new ClaimGuidGenerator( $itemId );

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
