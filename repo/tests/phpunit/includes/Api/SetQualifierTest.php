<?php

namespace Wikibase\Test\Repo\Api;

use DataValues\StringValue;
use FormatJson;
use UsageException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Api\SetQualifier
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group SetQualifierTest
 *
 * @group medium
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 * @author Marius Hoch < hoo@online.de >
 */
class SetQualifierTest extends WikibaseApiTestCase {

	protected function setUp() {
		parent::setUp();

		static $hasEntities = false;

		if ( !$hasEntities ) {
			$this->initTestEntities( array( 'StringProp', 'Berlin' ) );
			$hasEntities = true;
		}
	}

	/**
	 * Creates a Snak of the given type with the given data.
	 *
	 * @param string $type
	 * @param mixed $data
	 *
	 * @return Snak
	 */
	private function getTestSnak( $type, $data ) {
		static $snaks = array();

		if ( !isset( $snaks[$type] ) ) {
			$prop = Property::newFromType( 'string' );
			$propertyId = $this->makeProperty( $prop )->getId();

			$snaks[$type] = new $type( $propertyId, $data );
			$this->assertInstanceOf( 'Wikibase\DataModel\Snak\Snak', $snaks[$type] );
		}

		return $snaks[$type];
	}

	/**
	 * Creates the given property in the database, if necessary.
	 *
	 * @param Property $property
	 *
	 * @return Property
	 */
	protected function makeProperty( Property $property ) {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$store->saveEntity( $property, 'testing', $GLOBALS['wgUser'], EDIT_NEW );
		return $property;
	}

	protected function getTestItem() {
		static $item = null;

		if ( !$item ) {
			$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

			$newItem = new Item();
			$store->saveEntity( $newItem, '', $GLOBALS['wgUser'], EDIT_NEW );

			$prop = Property::newFromType( 'string' );
			$propId = $this->makeProperty( $prop )->getId();
			$snak = new PropertyValueSnak( $propId, new StringValue( '^_^' ) );

			$guidGenerator = new GuidGenerator();
			$guid = $guidGenerator->newGuid( $newItem->getId() );
			$newItem->getStatements()->addNewStatement( $snak, null, null, $guid );

			$store->saveEntity( $newItem, '', $GLOBALS['wgUser'], EDIT_UPDATE );
			$item = $newItem;
		}

		return $item;
	}

	public function provideAddRequests() {
		return array(
			array( 'Wikibase\DataModel\Snak\PropertyNoValueSnak' ),
			array( 'Wikibase\DataModel\Snak\PropertySomeValueSnak' ),
			array( 'Wikibase\DataModel\Snak\PropertyValueSnak', new StringValue( 'o_O' ) )
		);
	}

	/**
	 * @dataProvider provideAddRequests
	 */
	public function testAddRequests( $snakType, $data = null ) {
		$item = $this->getTestItem();
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$guid = $statement->getGuid();

		$snak = $this->getTestSnak( $snakType, $data );

		$this->makeSetQualifierRequest( $guid, null, $snak, $item->getId() );

		// now the hash exists, so the same request should fail
		$this->setExpectedException( UsageException::class );
		$this->makeSetQualifierRequest( $guid, null, $snak, $item->getId() );
	}

	public function provideChangeRequests() {
		return array( array( 'Wikibase\DataModel\Snak\PropertyValueSnak', new StringValue( 'o_O' ) ) );
	}

	/**
	 * @dataProvider provideChangeRequests
	 */
	public function testChangeRequests( $snakType, $data ) {
		$item = $this->getTestItem();
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$guid = $statement->getGuid();

		$snak = $this->getTestSnak( $snakType, $data );

		static $counter = 1;
		$hash = $snak->getHash();
		$newQualifier = new PropertyValueSnak( $snak->getPropertyId(), new StringValue( __METHOD__ . '#' . $counter++ ) );

		$this->makeSetQualifierRequest( $guid, $hash, $newQualifier, $item->getId() );

		// now the hash changed, so the same request should fail
		$this->setExpectedException( UsageException::class );
		$this->makeSetQualifierRequest( $guid, $hash, $newQualifier, $item->getId() );
	}

	protected function makeSetQualifierRequest( $statementGuid, $snakhash, Snak $qualifier, EntityId $entityId ) {
		$params = array(
			'action' => 'wbsetqualifier',
			'claim' => $statementGuid,
			'snakhash' => $snakhash,
			'snaktype' => $qualifier->getType(),
			'property' => $qualifier->getPropertyId()->getSerialization(),
		);

		if ( $qualifier instanceof PropertyValueSnak ) {
			$dataValue = $qualifier->getDataValue();
			$params['value'] = FormatJson::encode( $dataValue->getArrayValue() );
		}

		$this->makeValidRequest( $params );

		/** @var StatementListProvider $entity */
		$entity = WikibaseRepo::getDefaultInstance()->getEntityLookup()->getEntity( $entityId );

		$statements = $entity->getStatements();

		$statement = $statements->getFirstStatementWithGuid( $params['claim'] );

		$this->assertNotNull( $statement );
		$this->assertTrue(
			$statement->getQualifiers()->hasSnak( $qualifier ),
			'The qualifier should exist in the qualifier list after making the request'
		);
	}

	protected function makeValidRequest( array $params ) {
		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'pageinfo', $resultArray, 'top level element has a pageinfo key' );
		$this->assertArrayHasKey( 'claim', $resultArray, 'top level element has a statement key' );

		return $resultArray;
	}

	/**
	 * @dataProvider invalidRequestProvider
	 */
	public function testInvalidRequest( $itemHandle, $guid, $propertyHande, $snakType, $value, $error ) {
		$itemId = new ItemId( EntityTestHelper::getId( $itemHandle ) );
		$item = WikibaseRepo::getDefaultInstance()->getEntityLookup()->getEntity( $itemId );

		$propertyId = EntityTestHelper::getId( $propertyHande );

		if ( $guid === null ) {
			/** @var Item $item */
			$statements = $item->getStatements()->toArray();
			/** @var Statement $statement */
			$statement = reset( $statements );
			$guid = $statement->getGuid();
		}

		if ( !is_string( $value ) ) {
			$value = json_encode( $value );
		}

		$params = array(
			'action' => 'wbsetqualifier',
			'claim' => $guid,
			'property' => $propertyId,
			'snaktype' => $snakType,
			'value' => $value,
		);

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Invalid request did not raise an error' );
		} catch ( UsageException $ex ) {
			$this->assertEquals( $error, $ex->getCodeString(), 'Invalid request raised correct error' );
		}
	}

	public function invalidRequestProvider() {
		return array(
			'bad guid 1' => array( 'Berlin', 'xyz', 'StringProp', 'value', 'abc', 'invalid-guid' ),
			'bad guid 2' => array( 'Berlin', 'x$y$z', 'StringProp', 'value', 'abc', 'invalid-guid' ),
			'bad guid 3' => array( 'Berlin', 'i1813$358fa2a0-4345-82b6-12a4-7b0fee494a5f', 'StringProp', 'value', 'abc', 'invalid-guid' ),
			'bad snak type' => array( 'Berlin', null, 'StringProp', 'alksdjf', 'abc', 'unknown_snaktype' ),
			'bad snak value' => array( 'Berlin', null, 'StringProp', 'value', '"   "', 'modification-failed' ),
		);
	}

}
