<?php

namespace Wikibase\Repo\Tests\Api;

use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use ApiUsageException;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers Wikibase\Repo\Api\SetReference
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
	 * @var PropertyId[]
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

	protected function setUp() {
		parent::setUp();

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$store = $wikibaseRepo->getEntityStore();

		if ( !self::$propertyIds ) {
			self::$propertyIds = [];

			for ( $i = 0; $i < 4; $i++ ) {
				$property = Property::newFromType( 'string' );
				$store->saveEntity( $property, '', $GLOBALS['wgUser'], EDIT_NEW );

				self::$propertyIds[] = $property->getId();
			}

			$this->initTestEntities( [ 'StringProp', 'Berlin' ] );
		}

		$this->serializerFactory = new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
		);
		$this->deserializerFactory = $wikibaseRepo->getBaseDataModelDeserializerFactory();
	}

	/**
	 * @param Reference[] $references references to be created with
	 *
	 * @return Statement
	 */
	private function getNewStatement( array $references = [] ) {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		// Create a new empty item
		$item = new Item();
		$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_NEW );

		$statementGuid = $item->getId()->getSerialization() . '$D8505CDA-25E4-4334-AG93-A3290BCD9C0P';

		$item->getStatements()->addNewStatement(
			new PropertyNoValueSnak( self::$propertyIds[0] ),
			null,
			$references,
			$statementGuid
		);
		$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_UPDATE );

		return $item->getStatements()->getFirstStatementWithGuid( $statementGuid );
	}

	public function testRequests() {
		$reference = new Reference( [ new PropertySomeValueSnak( 100 ) ] );
		$referenceHash = $reference->getHash();
		$statement = $this->getNewStatement( [ $reference ] );
		$guid = $statement->getGuid();

		// Replace the reference with this new one
		$newReference = new Reference( new SnakList( [
			new PropertyNoValueSnak( self::$propertyIds[1] )
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
		/** @var Reference[] $references */
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

		$this->assertEquals( $statement->getReferences()->indexOf( $references[0] ), 0 );
	}

	/**
	 * @param string|null $statementGuid
	 * @param string $referenceHash
	 * @param Reference|array $reference Reference object or serialized reference
	 * @param int|null $index
	 *
	 * @return array Serialized reference
	 */
	protected function makeValidRequest( $statementGuid, $referenceHash, $reference, $index = null ) {
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

		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
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

	protected function makeInvalidRequest(
		$statementGuid,
		$referenceHash,
		$snaksJson,
		$snaksOrderJson,
		$expectedErrorCode
	) {
		$params = $this->generateRequestParams(
			$statementGuid,
			$snaksJson,
			$snaksOrderJson,
			$referenceHash
		);

		try {
			$this->doApiRequestWithToken( $params );
			$this->assertFalse( true, 'Invalid request should raise an exception' );
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
	protected function serializeReference( $reference ) {
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
	 * @return Reference Reference
	 */
	protected function unserializeReference( $reference ) {
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
		$statementGuid,
		$snaksJson,
		$snaksOrderJson = null,
		$referenceHash = null,
		$index = null
	) {
		$params = [
			'action' => 'wbsetreference',
			'statement' => $statementGuid,
			'snaks' => $snaksJson,
		];

		if ( !is_null( $snaksOrderJson ) ) {
			$params['snaks-order'] = $snaksOrderJson;
		}

		if ( !is_null( $referenceHash ) ) {
			$params['reference'] = $referenceHash;
		}

		if ( !is_null( $index ) ) {
			$params['index'] = $index;
		}

		return $params;
	}

	/**
	 * @dataProvider provideInvalidSerializations
	 */
	public function testInvalidSerialization( $snaksSerialization ) {
		$this->setExpectedException( ApiUsageException::class );
		$params = [
			'action' => 'wbsetreference',
			'statement' => 'Foo$Guid',
			'snaks' => $snaksSerialization
		];
		$this->doApiRequestWithToken( $params );
	}

	public function provideInvalidSerializations() {
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
	public function testInvalidRequest( $itemHandle, $guid, $referenceValue, $referenceHash, $error ) {
		$itemId = new ItemId( EntityTestHelper::getId( $itemHandle ) );
		$item = WikibaseRepo::getDefaultInstance()->getEntityLookup()->getEntity( $itemId );

		if ( $guid === null ) {
			/** @var StatementListProvider $item */
			$statements = $item->getStatements()->toArray();
			/** @var Statement $statement */
			$statement = reset( $statements );
			$guid = $statement->getGuid();
		}

		$prop = new PropertyId( EntityTestHelper::getId( 'StringProp' ) );
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

	public function invalidRequestProvider() {
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
