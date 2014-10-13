<?php

namespace Wikibase\Test\Api;

use DataValues\StringValue;
use FormatJson;
use UsageException;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Api\SetReference
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group SetReferenceTest
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 * @author H. Snater < mediawiki@snater.com >
 * @author Adam Shorland
 */
class SetReferenceTest extends WikibaseApiTestCase {

	private static $propertyIds;

	protected function setUp() {
		parent::setUp();

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		if ( !self::$propertyIds ) {
			self::$propertyIds = array();

			for ( $i = 0; $i < 4; $i++ ) {
				$property = Property::newFromType( 'string' );
				$store->saveEntity( $property, '', $GLOBALS['wgUser'], EDIT_NEW );

				self::$propertyIds[] = $property->getId();
			}

			$this->initTestEntities( array( 'StringProp', 'Berlin' ) );
		}
	}

	// TODO: clean this up so more of the input space can easily be tested
	// semi-blocked by cleanup of GUID handling in claims
	// can perhaps tseal from RemoveReferencesTest
	public function testRequests() {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$item = Item::newEmpty();
		$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_NEW );

		$statement = $item->newClaim( new PropertyNoValueSnak( self::$propertyIds[0] ) );
		$statement->setGuid( $item->getId()->getSerialization() . '$D8505CDA-25E4-4334-AG93-A3290BCD9C0P' );

		$reference = new Reference( new SnakList(
			array( new PropertySomeValueSnak( 100 ) )
		) );

		$statement->getReferences()->addReference( $reference );

		$item->addClaim( $statement );

		$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_UPDATE );

		$referenceHash = $reference->getHash();

		$reference = new Reference( new SnakList(
			array( new PropertyNoValueSnak( self::$propertyIds[1] ) )
		) );

		$serializedReference = $this->makeValidRequest(
			$statement->getGuid(),
			$referenceHash,
			$reference
		);

		// Since the reference got modified, the hash should no longer match
		$this->makeInvalidRequest(
			$statement->getGuid(),
			$referenceHash,
			$reference
		);

		$referenceHash = $serializedReference['hash'];

		$reference = new Reference( new SnakList(
			array(
				new PropertyNoValueSnak( self::$propertyIds[0] ),
				new PropertyNoValueSnak( self::$propertyIds[1] ),
			)
		) );

		// Set reference with two snaks:
		$serializedReference = $this->makeValidRequest(
			$statement->getGuid(),
			$referenceHash,
			$reference
		);

		$referenceHash = $serializedReference['hash'];

		// Reorder reference snaks by moving the last property id to the front:
		$firstPropertyId = array_shift( $serializedReference['snaks-order'] );
		array_push( $serializedReference['snaks-order'], $firstPropertyId );

		// Make another request with reordered snaks-order:
		$this->makeValidRequest(
			$statement->getGuid(),
			$referenceHash,
			$serializedReference
		);
	}

	public function testRequestWithInvalidProperty() {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$item = Item::newEmpty();
		$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_NEW );

		// Create a statement to act upon:
		$statement = $item->newClaim( new PropertyNoValueSnak( self::$propertyIds[0] ) );
		$statement->setGuid(
			$item->getId()->getSerialization() . '$D8505CDA-25E4-4334-AG93-A3290BCD9C0P'
		);

		$item->addClaim( $statement );

		$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_UPDATE );

		$snak = new PropertySomeValueSnak( new PropertyId( 'P23728525' ) );
		$reference = new Reference( new SnakList( array( $snak ) ) );

		$this->makeInvalidRequest( $statement->getGuid(), null, $reference, 'modification-failed' );
	}

	public function testSettingIndex() {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$item = Item::newEmpty();
		$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_NEW );

		// Create a statement to act upon:
		$statement = $item->newClaim( new PropertyNoValueSnak( self::$propertyIds[0] ) );
		$statement->setGuid(
			$item->getId()->getSerialization() . '$D8505CDA-25E4-4334-AG93-A3290BCD9C0P'
		);

		// Pre-fill statement with three references:
		$references = array(
			new Reference( new SnakList( array( new PropertySomeValueSnak( self::$propertyIds[0] ) ) ) ),
			new Reference( new SnakList( array( new PropertySomeValueSnak( self::$propertyIds[1] ) ) ) ),
			new Reference( new SnakList( array( new PropertySomeValueSnak( self::$propertyIds[2] ) ) ) ),
		);

		foreach( $references as $reference ) {
			$statement->getReferences()->addReference( $reference );
		}

		$item->addClaim( $statement );

		$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_UPDATE );

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
			$referenceHash,
			$serializedReference,
			$index
		);

		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'reference', $resultArray, 'top level element has a reference key' );

		$serializedReference = $resultArray['reference'];

		unset( $serializedReference['lastrevid'] );

		$this->assertArrayEquals( $this->serializeReference( $reference ), $serializedReference );

		return $serializedReference;
	}

	protected function makeInvalidRequest(
		$statementGuid,
		$referenceHash,
		Reference $reference,
		$expectedErrorCode = 'no-such-reference'
	) {
		$serializedReference = $this->serializeReference( $reference );

		$params = $this->generateRequestParams( $statementGuid, $referenceHash, $serializedReference );

		try {
			$this->doApiRequestWithToken( $params );
			$this->assertFalse( true, 'Invalid request should raise an exception' );
		}
		catch ( UsageException $e ) {
			$this->assertEquals( $expectedErrorCode, $e->getCodeString(), 'Invalid request raised correct error' );
		}
	}

	/**
	 * Serializes a Reference object (if not serialized already).
	 *
	 * @param Reference|array $reference
	 * @return array
	 */
	protected function serializeReference( $reference ) {
		if ( !( $reference instanceof Reference ) ) {
			return $reference;
		} else {
			$serializerFactory = new SerializerFactory();
			$serializer = $serializerFactory->newSerializerForObject( $reference );
			return $serializer->getSerialized( $reference );
		}
	}

	/**
	 * Unserializes a serialized Reference object (if not unserialized already).
	 *
	 * @param array|Reference $reference
	 * @return Reference Reference
	 */
	protected function unserializeReference( $reference ) {
		if ( $reference instanceof Reference ) {
			return $reference;
		} else {
			unset( $reference['hash'] );
			$serializerFactory = new SerializerFactory();
			$unserializer = $serializerFactory->newUnserializerForClass( 'Wikibase\DataModel\Reference' );
			return $unserializer->newFromSerialization( $reference );
		}
	}

	/**
	 * Generates the parameters for a 'wbsetreference' API request.
	 *
	 * @param string $statementGuid
	 * @param string $referenceHash
	 * @param array $serializedReference
	 * @param int|null $index
	 *
	 * @return array
	 */
	protected function generateRequestParams(
		$statementGuid,
		$referenceHash,
		$serializedReference,
		$index = null
	) {
		$params = array(
			'action' => 'wbsetreference',
			'statement' => $statementGuid,
			'snaks' => FormatJson::encode( $serializedReference['snaks'] ),
			'snaks-order' => FormatJson::encode( $serializedReference['snaks-order'] ),
		);

		if( !is_null( $referenceHash ) ) {
			$params['reference'] = $referenceHash;
		}

		if( !is_null( $index ) ) {
			$params['index'] = $index;
		}

		return $params;
	}

	/**
	 * Currently tests bad calender model
	 * @todo test more bad serializations...
	 */
	public function testInvalidSerialization() {
		$this->setExpectedException( 'UsageException' );
		$params = array(
			'action' => 'wbsetreference',
			'statement' => 'Foo$Guid',
			'snaks' => '{
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
}',
		);
		$this->doApiRequestWithToken( $params );
	}


	/**
	 * @dataProvider invalidRequestProvider
	 */
	public function testInvalidRequest( $itemHandle, $claimGuid, $referenceValue, $referenceHash, $error ) {
		$itemId = new ItemId( EntityTestHelper::getId( $itemHandle ) );
		$item = WikibaseRepo::getDefaultInstance()->getEntityLookup()->getEntity( $itemId );

		if ( $claimGuid === null ) {
			$claims = $item->getClaims();

			/* @var Claim $claim */
			$claim = reset( $claims );
			$claimGuid = $claim->getGuid();
		}

		$prop = new PropertyId( EntityTestHelper::getId( 'StringProp' ) );
		$snak = new PropertyValueSnak( $prop, new StringValue( $referenceValue ) );
		$reference = new Reference( new SnakList( array( $snak ) ) );

		$serializedReference = $this->serializeReference( $reference );

		$params = array(
			'action' => 'wbsetreference',
			'statement' => $claimGuid,
			'snaks' => FormatJson::encode( $serializedReference['snaks'] ),
			'snaks-order' => FormatJson::encode( $serializedReference['snaks-order'] ),
		);

		if ( $referenceHash ) {
			$params['reference'] = $referenceHash;
		}

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Invalid request did not raise an error' );
		} catch ( \UsageException $e ) {
			$this->assertEquals( $error, $e->getCodeString(),  'Invalid claim guid raised correct error' );
		}
	}

	public function invalidRequestProvider() {
		return array(
			'bad guid 1' => array( 'Berlin', 'xyz', 'good', '', 'invalid-guid' ),
			'bad guid 2' => array( 'Berlin', 'x$y$z', 'good', '',  'invalid-guid' ),
			'bad guid 3' => array( 'Berlin', 'i1813$358fa2a0-4345-82b6-12a4-7b0fee494a5f', 'good', '', 'invalid-guid' ),
			'bad snak value' => array( 'Berlin', null, '    ', '', 'modification-failed' ),
		);
	}

}
