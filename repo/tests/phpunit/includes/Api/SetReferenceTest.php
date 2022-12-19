<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;
use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use Wikibase\DataModel\Deserializers\DeserializerFactory;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Repo\Api\SetReference
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 */
class SetReferenceTest extends WikibaseApiTestCase {

	/**
	 * @var NumericPropertyId[]
	 */
	private static $propertyIds;

	/**
	 * @var SerializerFactory
	 */
	private $serializerFactory;

	/**
	 * @var DeserializerFactory
	 */
	private $deserializerFactory;

	protected function setUp(): void {
		parent::setUp();

		$store = $this->getEntityStore();

		if ( !self::$propertyIds ) {
			self::$propertyIds = [];

			for ( $i = 0; $i < 4; $i++ ) {
				$property = Property::newFromType( 'string' );
				$store->saveEntity( $property, '', $this->user, EDIT_NEW );

				self::$propertyIds[] = $property->getId();
			}

			$this->initTestEntities( [ 'StringProp', 'Berlin' ] );
		}

		$this->serializerFactory = new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
		);
		$this->deserializerFactory = WikibaseRepo::getBaseDataModelDeserializerFactory();
	}

	/**
	 * @param Reference[] $references references to be created with
	 *
	 * @return Statement
	 */
	private function getNewStatement( array $references ): Statement {
		$store = $this->getEntityStore();
		// Create a new empty item
		$item = new Item();
		$store->saveEntity( $item, '', $this->user, EDIT_NEW );

		$statementGuid = $item->getId()->getSerialization() . '$D8505CDA-25E4-4334-AG93-A3290BCD9C0P';

		$item->getStatements()->addNewStatement(
			new PropertyNoValueSnak( self::$propertyIds[0] ),
			null,
			$references,
			$statementGuid
		);
		$store->saveEntity( $item, '', $this->user, EDIT_UPDATE );

		return $item->getStatements()->getFirstStatementWithGuid( $statementGuid );
	}

	public function testRequests() {
		$reference = new Reference( [ new PropertySomeValueSnak( 100 ) ] );
		$referenceHash = $reference->getHash();
		$statement = $this->getNewStatement( [ $reference ] );
		$guid = $statement->getGuid();

		// Replace the reference with this new one
		$newReference = new Reference( new SnakList( [
			new PropertyNoValueSnak( self::$propertyIds[1] ),
		] ) );
		$serializedReferenceResult = $this->makeValidRequest(
			$guid,
			$referenceHash,
			$newReference
		);

		// Since the reference got modified, the hash should no longer match
		$propertyIdString = self::$propertyIds[0]->getSerialization();
		$this->makeInvalidRequest(
			$guid,
			$referenceHash,
			'{"' . $propertyIdString . '":[{"snaktype":"novalue","property":"' . $propertyIdString . '"}]}',
			'["' . $propertyIdString . '"]',
			'no-such-reference'
		);

		// Replace the previous reference with a reference with 2 snaks
		$aditionalReference = new Reference( new SnakList(
			[
				new PropertyNoValueSnak( self::$propertyIds[0] ),
				new PropertyNoValueSnak( self::$propertyIds[1] ),
			]
		) );
		$serializedReferenceResult = $this->makeValidRequest(
			$guid,
			$serializedReferenceResult['hash'],
			$aditionalReference
		);

		// Reorder reference snaks by moving the last property id to the front:
		$firstPropertyId = array_shift( $serializedReferenceResult['snaks-order'] );
		array_push( $serializedReferenceResult['snaks-order'], $firstPropertyId );
		$this->makeValidRequest(
			$guid,
			$serializedReferenceResult['hash'],
			$serializedReferenceResult
		);
	}

	public function testRequestWithInvalidProperty() {
		$reference = new Reference( [ new PropertySomeValueSnak( 100 ) ] );
		$statement = $this->getNewStatement( [ $reference ] );
		$guid = $statement->getGuid();

		$this->makeInvalidRequest(
			$guid,
			null,
			'{"P23728525":[{"snaktype":"somevalue","property":"P23728525"}]}',
			'["P23728525"]',
			'modification-failed'
		);
	}

	public function testSettingIndex() {
		$references = [
			new Reference( new SnakList( [ new PropertySomeValueSnak( self::$propertyIds[0] ) ] ) ),
			new Reference( new SnakList( [ new PropertySomeValueSnak( self::$propertyIds[1] ) ] ) ),
			new Reference( new SnakList( [ new PropertySomeValueSnak( self::$propertyIds[2] ) ] ) ),
		];
		$statement = $this->getNewStatement( $references );

		$this->makeValidRequest(
			$statement->getGuid(),
			$references[2]->getHash(),
			$references[2],
			0
		);

		$this->assertSame( 0, $statement->getReferences()->indexOf( $references[0] ) );
	}

	/**
	 * @param string|null $statementGuid
	 * @param string $referenceHash
	 * @param Reference|array $reference Reference object or serialized reference
	 * @param int|null $index
	 *
	 * @return array Serialized reference
	 */
	protected function makeValidRequest( ?string $statementGuid, string $referenceHash, $reference, ?int $index = null ): array {
		$serializedReference = $this->serializeReference( $reference );
		$reference = $this->unserializeReference( $reference );

		$params = $this->generateRequestParams(
			$statementGuid,
			json_encode( $serializedReference['snaks'] ),
			json_encode( $serializedReference['snaks-order'] ),
			$referenceHash,
			$index
		);

		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertIsArray( $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'reference', $resultArray, 'top level element has a reference key' );

		$serializedReference = $resultArray['reference'];

		unset( $serializedReference['lastrevid'] );

		foreach ( $serializedReference['snaks'] as &$propertyGroup ) {
			foreach ( $propertyGroup as &$snak ) {
				$this->assertArrayHasKey( 'datatype', $snak );
				unset( $snak['datatype'] );
				$this->assertArrayHasKey( 'hash', $snak );
				unset( $snak['hash'] );
			}
		}

		$this->assertArrayEquals( $this->serializeReference( $reference ), $serializedReference );

		return $serializedReference;
	}

	public function testSetReferenceWithTag() {
		$references = [
			new Reference( new SnakList( [ new PropertySomeValueSnak( self::$propertyIds[0] ) ] ) ),
			new Reference( new SnakList( [ new PropertySomeValueSnak( self::$propertyIds[1] ) ] ) ),
		];
		$statement = $this->getNewStatement( $references );

		$newReference = new Reference(
			new SnakList( [ new PropertySomeValueSnak( self::$propertyIds[2] ) ] )
		);
		$serializedReference = $this->serializeReference( $newReference );

		$this->assertCanTagSuccessfulRequest( $this->generateRequestParams(
			$statement->getGuid(),
			json_encode( $serializedReference['snaks'] ),
			json_encode( $serializedReference['snaks-order'] )
		) );
	}

	public function testReturnsNormalizedData(): void {
		$propertyId = $this->createUppercaseStringTestProperty();

		$statement = $this->getNewStatement( [ /* no references yet */ ] );
		$guid = $statement->getGuid();

		$reference = new Reference( new SnakList( [ new PropertyValueSnak(
			$propertyId,
			new StringValue( 'a string' )
		) ] ) );
		$serialized = $this->serializeReference( $reference );
		$params = $this->generateRequestParams( $guid, json_encode( $serialized['snaks'] ) );

		[ $response ] = $this->doApiRequestWithToken( $params );

		$responseSnak = $response['reference']['snaks'][$propertyId->getSerialization()][0];
		$this->assertSame( 'A STRING', $responseSnak['datavalue']['value'] );
	}

	protected function makeInvalidRequest(
		string $statementGuid,
		?string $referenceHash,
		string $snaksJson,
		string $snaksOrderJson,
		string $expectedErrorCode
	) {
		$params = $this->generateRequestParams(
			$statementGuid,
			$snaksJson,
			$snaksOrderJson,
			$referenceHash
		);

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Invalid request should raise an exception' );
		} catch ( ApiUsageException $e ) {
			$msg = TestingAccessWrapper::newFromObject( $e )->getApiMessage();
			$this->assertEquals(
				$expectedErrorCode,
				$msg->getApiCode(),
				'Invalid request raised correct error'
			);
		}
	}

	/**
	 * Serializes a Reference object (if not serialized already).
	 *
	 * @param Reference|array $reference
	 * @return array
	 */
	protected function serializeReference( $reference ): array {
		if ( $reference instanceof Reference ) {
			$reference = $this->serializerFactory
				->newReferenceSerializer()
				->serialize( $reference );
		}
		return $reference;
	}

	/**
	 * Unserializes a serialized Reference object (if not unserialized already).
	 *
	 * @param array|Reference $reference
	 * @return Reference
	 */
	protected function unserializeReference( $reference ): Reference {
		if ( is_array( $reference ) ) {
			$reference = $this->deserializerFactory
				->newReferenceDeserializer()
				->deserialize( $reference );
		}
		return $reference;
	}

	/**
	 * Generates the parameters for a 'wbsetreference' API request.
	 *
	 * @param string $statementGuid
	 * @param string $snaksJson
	 * @param string|null $snaksOrderJson
	 * @param string|null $referenceHash
	 * @param int|null $index
	 *
	 * @return array
	 */
	protected function generateRequestParams(
		string $statementGuid,
		string $snaksJson,
		?string $snaksOrderJson = null,
		?string $referenceHash = null,
		?int $index = null
	): array {
		$params = [
			'action' => 'wbsetreference',
			'statement' => $statementGuid,
			'snaks' => $snaksJson,
		];

		if ( $snaksOrderJson !== null ) {
			$params['snaks-order'] = $snaksOrderJson;
		}

		if ( $referenceHash !== null ) {
			$params['reference'] = $referenceHash;
		}

		if ( $index !== null ) {
			$params['index'] = $index;
		}

		return $params;
	}

	/**
	 * @dataProvider provideInvalidSerializations
	 */
	public function testInvalidSerialization( string $snaksSerialization ) {
		$this->expectException( ApiUsageException::class );
		$params = [
			'action' => 'wbsetreference',
			'statement' => 'Foo$Guid',
			'snaks' => $snaksSerialization,
		];
		$this->doApiRequestWithToken( $params );
	}

	public function provideInvalidSerializations(): iterable {
		return [
			[ '{
				 "P813":[
						{
							 "snaktype":"value",
							 "property":"P813",
							 "datavalue":{
									"value":{
										 "time":"+00000002013-10-05T00:00:00Z",
										 "timezone":0,
										 "before":0,
										 "after":0,
										 "precision":11,
										 "calendarmodel":"FOOBAR :D"
									},
									"type":"time"
							 }
						}
				 ]
			}' ],
			[ '{
				 "P813":[
						{
							 "snaktype":"wubbledubble",
							 "property":"P813",
							 "datavalue":{
									"value":{
										 "time":"+00000002013-10-05T00:00:00Z",
										 "timezone":0,
										 "before":0,
										 "after":0,
										 "precision":11,
										 "calendarmodel":"http:\/\/www.wikidata.org\/entity\/Q1985727"
									},
									"type":"time"
							 }
						}
				 ]
			}' ],
		];
	}

	/**
	 * @dataProvider invalidRequestProvider
	 */
	public function testInvalidRequest( string $itemHandle, ?string $guid, string $referenceValue, string $referenceHash, string $error ) {
		$itemId = new ItemId( EntityTestHelper::getId( $itemHandle ) );
		$item = WikibaseRepo::getEntityLookup()->getEntity( $itemId );

		if ( $guid === null ) {
			/** @var StatementListProvider $item */
			$statements = $item->getStatements()->toArray();
			/** @var Statement $statement */
			$statement = reset( $statements );
			$guid = $statement->getGuid();
		}

		$prop = new NumericPropertyId( EntityTestHelper::getId( 'StringProp' ) );
		$snak = new PropertyValueSnak( $prop, new StringValue( $referenceValue ) );
		$reference = new Reference( new SnakList( [ $snak ] ) );

		$serializedReference = $this->serializeReference( $reference );

		$params = [
			'action' => 'wbsetreference',
			'statement' => $guid,
			'snaks' => json_encode( $serializedReference['snaks'] ),
			'snaks-order' => json_encode( $serializedReference['snaks-order'] ),
		];

		if ( $referenceHash ) {
			$params['reference'] = $referenceHash;
		}

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Invalid request did not raise an error' );
		} catch ( ApiUsageException $ex ) {
			$msg = TestingAccessWrapper::newFromObject( $ex )->getApiMessage();
			$this->assertEquals( $error, $msg->getApiCode(), 'Invalid request raised correct error' );
		}
	}

	public function invalidRequestProvider(): iterable {
		return [
			'bad guid 1' =>
				[ 'Berlin', 'xyz', 'good', '', 'invalid-guid' ],
			'bad guid 2' =>
				[ 'Berlin', 'x$y$z', 'good', '',  'invalid-guid' ],
			'bad guid 3' =>
				[ 'Berlin', 'i1813$358fa2a0-4345-82b6-12a4-7b0fee494a5f', 'good', '', 'invalid-guid' ],
			'bad snak value' =>
				[ 'Berlin', null, '    ', '', 'modification-failed' ],
		];
	}

}
