<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;
use DataValues\NumberValue;
use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use FormatJson;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Repo\Api\SetClaim
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Addshore
 */
class SetClaimTest extends WikibaseApiTestCase {

	private static $propertyIds;

	protected function setUp(): void {
		parent::setUp();

		if ( !self::$propertyIds ) {
			self::$propertyIds = $this->getPropertyIds();
		}
	}

	private function getPropertyIds(): array {
		$store = $this->getEntityStore();

		$propertyIds = [];

		for ( $i = 0; $i < 4; $i++ ) {
			$property = Property::newFromType( 'string' );

			$store->saveEntity( $property, 'testing', $this->user, EDIT_NEW );

			$propertyIds[] = $property->getId();
		}

		return $propertyIds;
	}

	/**
	 * @return Snak[]
	 */
	private function getSnaks(): array {
		$snaks = [];

		$snaks[] = new PropertyNoValueSnak( self::$propertyIds[0] );
		$snaks[] = new PropertySomeValueSnak( self::$propertyIds[1] );
		$snaks[] = new PropertyValueSnak( self::$propertyIds[2], new StringValue( 'o_O' ) );

		return $snaks;
	}

	/**
	 * @return Statement[]
	 */
	private function getStatements(): array {
		$statements = [];

		$ranks = [
			Statement::RANK_DEPRECATED,
			Statement::RANK_NORMAL,
			Statement::RANK_PREFERRED,
		];

		$snaks = $this->getSnaks();
		$snakList = new SnakList( $snaks );
		$mainSnak = $snaks[0];
		$statement = new Statement( $mainSnak );
		$statement->setRank( $ranks[array_rand( $ranks )] );
		$statements[] = $statement;

		foreach ( $snaks as $snak ) {
			$statement = unserialize( serialize( $statement ) );
			$statement->getReferences()->addReference( new Reference( new SnakList( [ $snak ] ) ) );
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
		$this->overrideConfigValue( 'RateLimits',
			[ 'wikibase-idgenerator' => [ '&can-bypass' => true, 'user' => [ 1000, 60 ] ] ] );
		$store = $this->getEntityStore();
		$guidGenerator = new GuidGenerator();

		$statements = $this->getStatements();

		foreach ( $statements as $statement ) {
			$entity = new Item();
			$store->saveEntity( $entity, 'setclaimtest', $this->user, EDIT_NEW );
			$entityId = $entity->getId();

			$guid = $guidGenerator->newGuid( $entityId );

			$statement->setGuid( $guid );

			// Addition request
			$this->makeRequestAndAssertResult( $statement, $entityId, 1, 'addition request' );

			// Reorder qualifiers
			if ( count( $statement->getQualifiers() ) > 0 ) {
				// Simply reorder the qualifiers by putting the first qualifier to the end. This is
				// supposed to be done in the serialized representation since changing the actual
				// object might apply intrinsic sorting.
				$serializerFactory = new SerializerFactory( new DataValueSerializer() );
				$statementSerializer = $serializerFactory->newStatementSerializer();
				$serialized = $statementSerializer->serialize( $statement );
				$firstPropertyId = array_shift( $serialized['qualifiers-order'] );
				array_push( $serialized['qualifiers-order'], $firstPropertyId );
				$this->makeRequestAndAssertResult( $serialized, $entityId, 1, 'reorder qualifiers' );
			}

			$newSnak = new PropertyValueSnak( $statement->getPropertyId(), new StringValue( '\o/' ) );
			$newStatement = new Statement( $newSnak );
			$newStatement->setGuid( $guid );

			// Update request
			$this->makeRequestAndAssertResult( $newStatement, $entityId, 1, 'update request' );
		}
	}

	public function testSetClaimWithTag() {
		$store = $this->getEntityStore();

		$item = new Item();
		$store->saveEntity( $item, 'setclaimtest', $this->user, EDIT_NEW );

		$this->assertCanTagSuccessfulRequest( [
			'action' => 'wbsetclaim',
			'claim' => '{
				"id":"' . $item->getId() . '$5627445f-43cb-ed6d-3adb-760e85bd17ee",
				"type":"claim",
				"mainsnak":{"snaktype":"value","property":"' . self::$propertyIds[0] . '","datavalue":{"value":"City","type":"string"}}
			}',
		] );
	}

	private function getInvalidCases() {
		$store = $this->getEntityStore();

		$item = new Item();
		$store->saveEntity( $item, 'setclaimtest', $this->user, EDIT_NEW );
		$q17 = $item->getId();

		$property = Property::newFromType( 'string' );
		$store->saveEntity( $property, 'setclaimtest', $this->user, EDIT_NEW );
		$p11 = $property->getId();

		$entity = new Item();
		$store->saveEntity( $entity, 'setclaimtest', $this->user, EDIT_NEW );
		$deletedEntityId = $entity->getId();
		$store->deleteEntity( $deletedEntityId, 'setclaimtest', $this->user );

		$property = Property::newFromType( 'string' );
		$store->saveEntity( $property, 'setclaimtest', $this->user, EDIT_NEW );
		$px = $property->getId();
		$store->deleteEntity( $px, 'setclaimtest', $this->user );

		$goodSnak = new PropertyValueSnak( $p11, new StringValue( 'good' ) );
		$badSnak = new PropertyValueSnak( $p11, new StringValue( ' x ' ) );
		$brokenSnak = new PropertyValueSnak( $p11, new NumberValue( 23 ) );
		$obsoleteSnak = new PropertyValueSnak( $px, new StringValue( ' x ' ) );

		$guidGenerator = new GuidGenerator();

		$cases = [];

		$statement = new Statement( $badSnak );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$cases['invalid value in main snak'] = [ $q17, $statement, 'modification-failed' ];

		$statement = new Statement( $brokenSnak );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$cases['mismatching value in main snak'] = [ $q17, $statement, 'modification-failed' ];

		$statement = new Statement( $obsoleteSnak );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$cases['obsolete snak using deleted property'] = [ $q17, $statement, 'modification-failed' ];

		$statement = new Statement( $goodSnak );
		$statement->setGuid( $guidGenerator->newGuid( $deletedEntityId ) );
		$cases['good claim for deleted entity'] = [ $deletedEntityId, $statement, 'no-such-entity' ];

		$statement = new Statement( $goodSnak );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$statement->setQualifiers( new SnakList( [ $badSnak ] ) );
		$cases['bad snak in qualifiers'] = [ $q17, $statement, 'modification-failed' ];

		$statement = new Statement( $goodSnak );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$statement->setQualifiers( new SnakList( [ $brokenSnak ] ) );
		$cases['mismatching value in qualifier'] = [ $q17, $statement, 'modification-failed' ];

		$statement = new Statement( $goodSnak );
		$reference = new Reference( new SnakList( [ $badSnak ] ) );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$statement->setReferences( new ReferenceList( [ $reference ] ) );
		$cases['bad snak in reference'] = [ $q17, $statement, 'modification-failed' ];

		$statement = new Statement( $goodSnak );
		$reference = new Reference( new SnakList( [ $badSnak ] ) );
		$statement->setGuid( $guidGenerator->newGuid( $q17 ) );
		$statement->setReferences( new ReferenceList( [ $reference ] ) );
		$cases['mismatching value in reference'] = [ $q17, $statement, 'modification-failed' ];

		$statement = new Statement( $goodSnak );
		$statement->setGuid( 'XXXX' );
		$cases['invalid GUID'] = [ $deletedEntityId, $statement, 'invalid-claim' ];

		// Statement with a bad datavalue id, for example QQ1234
		// https://phabricator.wikimedia.org/T200340
		$statement = json_decode( '{
                "mainsnak": {
                    "snaktype": "value",
                    "property": "' . $p11->getSerialization() . '",
                    "hash": "481c0a0ccbe34a98f027fbdd5b202a54c98f3494",
                    "datavalue": {
                        "value": {
                            "entity-type": "item",
                            "numeric-id": 4288,
                            "id": "Q' . $q17->getSerialization() . '"
                        },
                        "type": "wikibase-entityid"
                    },
                    "datatype": "wikibase-item"
                },
                "type": "statement",
                "id": "' . $q17->getSerialization() . '$151fed00-42f6-4125-0316-736e56a12026",
                "rank": "normal"
            }', true );
		$cases['invalid datavalue id'] = [ $q17, $statement, 'modification-failed' ];

		return $cases;
	}

	public function testAddInvalidClaim() {
		$cases = $this->getInvalidCases();

		foreach ( $cases as $label => $case ) {
			list( $entityId, $statement, $error ) = $case;

			$this->makeRequestAndAssertResult( $statement, $entityId, 1, $label, null, null, $error );
		}
	}

	public function testSetClaimAtIndex() {
		$store = $this->getEntityStore();

		$entity = new Item();

		$store->saveEntity( $entity, 'setclaimtest', $this->user, EDIT_NEW );
		$entityId = $entity->getId();

		$guidGenerator = new GuidGenerator();

		for ( $i = 1; $i <= 3; $i++ ) {
			$entity->getStatements()->addNewStatement(
				new PropertyNoValueSnak( $i ),
				null,
				null,
				$guidGenerator->newGuid( $entityId )
			);
		}

		$store->saveEntity( $entity, 'setclaimtest', $this->user, EDIT_UPDATE );

		$guid = $guidGenerator->newGuid( $entityId );
		foreach ( $this->getStatements() as $statement ) {
			$statement->setGuid( $guid );

			// Add new statement at index 2:
			$this->makeRequestAndAssertResult( $statement, $entityId, 4, 'addition request', 2 );
		}
	}

	public function testSetDuplicateMainSnakNoIgnore() {
		$mainSnak = new PropertyValueSnak( self::$propertyIds[0], new StringValue( 'good' ) );

		$store = $this->getEntityStore();

		$entity = new Item();
		$store->saveEntity( $entity, 'setclaimtest', $this->user, EDIT_NEW );
		$entityId = $entity->getId();
		$guidGenerator = new GuidGenerator();
		$entity->getStatements()->addNewStatement(
			$mainSnak,
			null,
			null,
			$guidGenerator->newGuid( $entityId )
		);
		$store->saveEntity( $entity, 'setclaimtest', $this->user, EDIT_UPDATE );

		$newStatement = new Statement( $mainSnak );
		$newStatement->setGuid( $guidGenerator->newGuid( $entityId ) );
		$this->makeRequestAndAssertResult( $newStatement, $entityId, 2, 'duplicate test' );
	}

	public function testSetDuplicateMainSnakWithIgnore() {
		$mainSnak = new PropertyValueSnak( self::$propertyIds[0], new StringValue( 'good' ) );

		$store = $this->getEntityStore();

		$entity = new Item();
		$store->saveEntity( $entity, 'setclaimtest', $this->user, EDIT_NEW );
		$entityId = $entity->getId();
		$guidGenerator = new GuidGenerator();
		$entity->getStatements()->addNewStatement(
			$mainSnak,
			null,
			null,
			$guidGenerator->newGuid( $entityId )
		);
		$store->saveEntity( $entity, 'setclaimtest', $this->user, EDIT_UPDATE );

		$newStatement = new Statement( $mainSnak );
		$newStatement->setGuid( $guidGenerator->newGuid( $entityId ) );
		$this->makeRequest(
			$newStatement,
			null,
			null,
			null,
			true
		);
		$this->assertStatementWasNotSet( $newStatement, $entityId );
	}

	/**
	 * @param Statement|array $statement Native or serialized statement object.
	 * @param int|null $index
	 * @param int|null $baserevid
	 * @param string|null $error
	 * @param bool|null $ignoreDuplicateMainSnak
	 * @return array
	 */
	private function makeRequest(
		$statement,
		?int $index,
		?int $baserevid,
		?string $error,
		?bool $ignoreDuplicateMainSnak = null
	): array {
		$serializerFactory = new SerializerFactory( new DataValueSerializer() );
		$statementSerializer = $serializerFactory->newStatementSerializer();

		if ( $statement instanceof Statement ) {
			$serialized = $statementSerializer->serialize( $statement );
		} else {
			$serialized = $statement;
		}

		$params = [
			'action' => 'wbsetclaim',
			'claim' => FormatJson::encode( $serialized ),
		];

		if ( $index !== null ) {
			$params['index'] = $index;
		}

		if ( $baserevid !== null ) {
			$params['baserevid'] = $baserevid;
		}

		if ( $ignoreDuplicateMainSnak !== null ) {
			$params['ignoreduplicatemainsnak'] = $ignoreDuplicateMainSnak;
		}

		$resultArray = $this->assertApiRequest( $params, $error );

		if ( $resultArray ) {
			return $resultArray;
		}
		return [];
	}

	/**
	 * @param Statement|array $statement Native or serialized statement object.
	 * @param EntityId $entityId
	 * @param int $expectedCount
	 * @param string $requestLabel A label to identify requests that are made in errors.
	 * @param int|null $index
	 * @param int|null $baserevid
	 * @param string|null $error
	 */
	private function makeRequestAndAssertResult(
		$statement,
		EntityId $entityId,
		int $expectedCount,
		string $requestLabel,
		?int $index = null,
		?int $baserevid = null,
		?string $error = null
	) {
		$resultArray = $this->makeRequest(
			$statement,
			$index,
			$baserevid,
			$error
		);

		if ( !( $statement instanceof Statement ) ) {
			$statementDeserializer = WikibaseRepo::getExternalFormatStatementDeserializer();
			$statement = $statementDeserializer->deserialize( $statement );
		}

		if ( $resultArray ) {
			$this->assertValidResponse( $resultArray );
			$this->assertStatementWasSet( $statement, $entityId, $expectedCount, $requestLabel );
		}
	}

	/**
	 * @param array $params
	 * @param string|null $error
	 *
	 * @return array|bool
	 */
	private function assertApiRequest( array $params, ?string $error ) {
		$resultArray = false;

		try {
			list( $resultArray, ) = $this->doApiRequestWithToken( $params );

			if ( $error !== null ) {
				$this->fail( "Did not cause expected error $error" );
			}
		} catch ( ApiUsageException $ex ) {
			if ( $error ) {
				/** @var \ApiMessage $msg */
				$msg = TestingAccessWrapper::newFromObject( $ex )->getApiMessage();
				$this->assertEquals( $error, $msg->getApiCode(), 'Wrong error code: ' . $msg->plain() );
			} else {
				$this->fail( "Caused unexpected error!" . $ex );
			}
		}

		return $resultArray;
	}

	private function assertValidResponse( array $resultArray ): void {
		$this->assertResultSuccess( $resultArray );
		$this->assertIsArray( $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'pageinfo', $resultArray, 'top level element has a pageinfo key' );
		$this->assertArrayHasKey( 'claim', $resultArray, 'top level element has a statement key' );

		if ( isset( $resultArray['claim']['qualifiers'] ) ) {
			$this->assertArrayHasKey( 'qualifiers-order', $resultArray['claim'],
				'"qualifiers-order" key is set when returning qualifiers' );
		}
	}

	/**
	 * @param Statement $statement
	 * @param EntityId $entityId
	 * @param int $expectedCount
	 * @param string $requestLabel A label to identify requests that are made in errors.
	 */
	private function assertStatementWasSet(
		Statement $statement,
		EntityId $entityId,
		int $expectedCount,
		string $requestLabel
	): void {
		$this->assertNotNull( $statement->getGuid(), 'Cannot search for statements with no GUID' );

		/** @var StatementListProvider $entity */
		$entity = WikibaseRepo::getEntityLookup()->getEntity( $entityId );

		$statements = $entity->getStatements();
		$savedStatement = $statements->getFirstStatementWithGuid( $statement->getGuid() );
		$this->assertNotNull( $savedStatement, "Statement list does not have statement after {$requestLabel}" );
		if ( count( $statement->getQualifiers() ) ) {
			$this->assertTrue( $statement->getQualifiers()->equals( $savedStatement->getQualifiers() ) );
		}

		$this->assertSame( $expectedCount, $statements->count(), "Statements count is wrong after {$requestLabel}" );
	}

	private function assertStatementWasNotSet(
		Statement $statement,
		EntityId $entityId
	): void {
		$this->assertNotNull( $statement->getGuid(), 'Cannot search for statements with no GUID' );

		/** @var StatementListProvider $entity */
		$entity = WikibaseRepo::getEntityLookup()->getEntity( $entityId );

		$statements = $entity->getStatements();
		$this->assertNull( $statements->getFirstStatementWithGuid( $statement->getGuid() ) );
	}

	/**
	 * @see Bug T60394 - "specified index out of bounds" issue when moving a statement
	 * @note A hack is  in place in ChangeOpStatement to allow this
	 */
	public function testBugT60394SpecifiedIndexOutOfBounds() {
		$store = $this->getEntityStore();

		// Save new entity with empty statements:
		$entity = new Item();
		$store->saveEntity( $entity, 'setclaimtest', $this->user, EDIT_NEW );

		// Update the same entity with a single statement:
		$entityId = $entity->getId();
		$guidGenerator = new GuidGenerator();
		$entity->getStatements()->addNewStatement(
			new PropertyNoValueSnak( self::$propertyIds[1] ),
			null,
			null,
			$guidGenerator->newGuid( $entityId )
		);
		$revision = $store->saveEntity( $entity, 'setclaimtest', $this->user, EDIT_UPDATE );

		// Add new statement at index 3 using the baserevid and a different property id
		$statement = new Statement( new PropertyNoValueSnak( self::$propertyIds[2] ) );
		$statement->setGuid( $guidGenerator->newGuid( $entityId ) );
		$this->makeRequestAndAssertResult( $statement, $entityId, 2, 'addition request', 3, $revision->getRevisionId() );
	}

	public function testBadPropertyError() {
		$store = $this->getEntityStore();

		$property = Property::newFromType( 'quantity' );
		$property = $store->saveEntity( $property, '', $this->user, EDIT_NEW )->getEntity();

		$entity = new Item();
		/** @var EntityDocument|StatementListProvider $entity */
		$entity = $store->saveEntity( $entity, '', $this->user, EDIT_NEW )->getEntity();

		$guidGenerator = new GuidGenerator();
		$statement = new Statement( new PropertyNoValueSnak( $property->getId() ) );
		$statement->setGuid( $guidGenerator->newGuid( $entity->getId() ) );

		$entity->getStatements()->addStatement( $statement );
		$store->saveEntity( $entity, '', $this->user, EDIT_UPDATE );

		// try to change the main snak's property
		$badProperty = Property::newFromType( 'string' );
		$badProperty = $store->saveEntity( $badProperty, '', $this->user, EDIT_NEW )->getEntity();

		$badSerialization = [
			'id' => $statement->getGuid(),
			'mainsnak' => [
				'snaktype' => 'novalue',
				'property' => $badProperty->getId()->getSerialization(),
			],
			'type' => 'statement',
			'rank' => 'normal',
		];

		$params = [
			'action' => 'wbsetclaim',
			'claim' => FormatJson::encode( $badSerialization ),
		];

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Changed main snak property did not raise an error' );
		} catch ( ApiUsageException $e ) {
			$msg = TestingAccessWrapper::newFromObject( $e )->getApiMessage();
			$this->assertEquals( 'modification-failed', $msg->getApiCode(), 'Changed main snak property' );
		}
	}

	public function testReturnsNormalizedData(): void {
		$propertyId = $this->createUppercaseStringTestProperty();

		$entity = $this->getEntityStore()
			->saveEntity( new Item(), '', $this->user, EDIT_NEW )
			->getEntity();

		$guidGenerator = new GuidGenerator();
		$statement = new Statement( new PropertyValueSnak(
			$propertyId,
			new StringValue( 'a string' )
		) );
		$statement->setGuid( $guidGenerator->newGuid( $entity->getId() ) );

		$response = $this->makeRequest( $statement, null, null, null );

		$this->assertValidResponse( $response );
		$this->assertSame( 'A STRING', $response['claim']['mainsnak']['datavalue']['value'] );
	}

}
