<?php

namespace Wikibase\Test\Api;

use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Item;
use Wikibase\ItemContent;
use Wikibase\PropertyContent;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\Reference;
use Wikibase\SnakList;

/**
 * Unit tests for the Wikibase\ApiSetReference class.
 *
 * @since 0.3
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
 */
class SetReferenceTest extends WikibaseApiTestCase {

	public function setUp() {
		static $hasProperties = false;
		if ( !$hasProperties ) {
			$prop = PropertyContent::newEmpty();
			$prop->getEntity()->setId( 42 );
			$prop->getEntity()->setDataTypeId( 'string' );
			$prop->save( 'testing' );

			$prop = PropertyContent::newEmpty();
			$prop->getEntity()->setId( 43 );
			$prop->getEntity()->setDataTypeId( 'string' );
			$prop->save( 'testing' );

			$hasProperties = true;
		}

		parent::setUp();
	}

	// TODO: clean this up so more of the input space can easily be tested
	// semi-blocked by cleanup of GUID handling in claims
	// can perhaps tseal from RemoveReferencesTest
	public function testRequests() {
		$item = \Wikibase\Item::newEmpty();
		$content = new \Wikibase\ItemContent( $item );
		$content->save( '', null, EDIT_NEW );

		$statement = $item->newClaim( new \Wikibase\PropertyNoValueSnak( 42 ) );
		$statement->setGuid( $item->getId()->getPrefixedId() . '$D8505CDA-25E4-4334-AG93-A3290BCD9C0P' );

		$reference = new \Wikibase\Reference( new \Wikibase\SnakList(
			array( new \Wikibase\PropertySomeValueSnak( 1 ) )
		) );

		$statement->getReferences()->addReference( $reference );

		$item->addClaim( $statement );

		$content->save( '' );

		$referenceHash = $reference->getHash();

		$reference = new \Wikibase\Reference( new \Wikibase\SnakList(
			array( new \Wikibase\PropertyNoValueSnak( 42 ) )
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

		$reference = new \Wikibase\Reference( new \Wikibase\SnakList(
			array(
				new \Wikibase\PropertyNoValueSnak( 42 ),
				new \Wikibase\PropertyNoValueSnak( 43 ),
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

	public function testSettingIndex() {
		$item = Item::newEmpty();
		$content = new ItemContent( $item );
		$content->save( '', null, EDIT_NEW );

		// Create a statement to act upon:
		$statement = $item->newClaim( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid(
			$item->getId()->getPrefixedId() . '$D8505CDA-25E4-4334-AG93-A3290BCD9C0P'
		);

		// Pre-fill statement with three references:
		$references = array(
			new Reference( new SnakList( array( new PropertySomeValueSnak( 1 ) ) ) ),
			new Reference( new SnakList( array( new PropertySomeValueSnak( 2 ) ) ) ),
			new Reference( new SnakList( array( new PropertySomeValueSnak( 3 ) ) ) ),
		);

		foreach( $references as $reference ) {
			$statement->getReferences()->addReference( $reference );
		}

		$item->addClaim( $statement );

		$content->save( '' );

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

	protected function makeInvalidRequest( $statementGuid, $referenceHash, Reference $reference ) {
		$serializedReference = $this->serializeReference( $reference );

		$params = $this->generateRequestParams( $statementGuid, $referenceHash, $serializedReference );

		try {
			$this->doApiRequestWithToken( $params );
			$this->assertFalse( true, 'Invalid request should raise an exception' );
		}
		catch ( \UsageException $e ) {
			$this->assertEquals( 'no-such-reference', $e->getCodeString(), 'Invalid request raised correct error' );
		}
	}

	/**
	 * Serializes a Reference object (if not serialized already).
	 *
	 * @param Reference|array $reference
	 * @return array
	 */
	protected function serializeReference( $reference ) {
		if( !is_a( $reference, '\Wikibase\Reference' ) ) {
			return $reference;
		} else {
			$options = new SerializationOptions();
			$serializerFactory = new SerializerFactory( $options );
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
		if( is_a( $reference, '\Wikibase\Reference' ) ) {
			return $reference;
		} else {
			unset( $reference['hash'] );
			$options = new SerializationOptions();
			$serializerFactory = new SerializerFactory( $options );
			$unserializer = $serializerFactory->newUnserializerForClass( '\Wikibase\Reference' );
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
			'reference' => $referenceHash,
			'snaks' => \FormatJson::encode( $serializedReference['snaks'] ),
			'snaks-order' => \FormatJson::encode( $serializedReference['snaks-order'] ),
		);

		if( !is_null( $index ) ) {
			$params['index'] = $index;
		}

		return $params;
	}

	/**
	 * @dataProvider invalidClaimProvider
	 */
	public function testInvalidClaimGuid( $claimGuid, $snakHash, $refHash, $expectedError ) {
		$params = array(
			'action' => 'wbsetreference',
			'statement' => $claimGuid,
			'snaks' => $snakHash,
			'reference' => $refHash,
		);

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( "Exception with code $expectedError expected" );
		} catch ( \UsageException $e ) {
			$this->assertEquals( $expectedError, $e->getCodeString(), 'Error code' );
		}
	}

	public function invalidClaimProvider() {
		$snak = new \Wikibase\PropertyValueSnak( 42, new \DataValues\StringValue( 'abc') );
		$snakHash = $snak->getHash();

		$reference = new \Wikibase\PropertyValueSnak( 42, new \DataValues\StringValue( 'def' ) );
		$refHash = $reference->getHash();

		return array(
			array( 'xyz', $snakHash, $refHash, 'invalid-guid' ),
			array( 'x$y$z', $snakHash, $refHash, 'invalid-guid' )
		);
	}

}
