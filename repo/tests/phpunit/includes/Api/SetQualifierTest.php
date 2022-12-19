<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;
use DataValues\StringValue;
use FormatJson;
use PHPUnit\Framework\Constraint\Constraint;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\StatementListProvidingEntity;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Repo\Api\SetQualifier
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
class SetQualifierTest extends WikibaseApiTestCase {

	protected function setUp(): void {
		parent::setUp();

		static $hasEntities = false;

		if ( !$hasEntities ) {
			$this->initTestEntities( [ 'StringProp', 'Berlin' ] );
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
	private function getTestSnak( string $type, $data ): Snak {
		static $snaks = [];

		if ( !isset( $snaks[$type] ) ) {
			$prop = Property::newFromType( 'string' );
			$propertyId = $this->makeProperty( $prop )->getId();

			$snaks[$type] = new $type( $propertyId, $data );
			$this->assertInstanceOf( Snak::class, $snaks[$type] );
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
	protected function makeProperty( Property $property ): Property {
		$store = $this->getEntityStore();

		$store->saveEntity( $property, 'testing', $this->user, EDIT_NEW );
		return $property;
	}

	protected function getTestItem(): Item {
		static $item = null;

		if ( !$item ) {
			$store = $this->getEntityStore();

			$newItem = new Item();
			$store->saveEntity( $newItem, '', $this->user, EDIT_NEW );

			$prop = Property::newFromType( 'string' );
			$propId = $this->makeProperty( $prop )->getId();
			$snak = new PropertyValueSnak( $propId, new StringValue( '^_^' ) );

			$guidGenerator = new GuidGenerator();
			$guid = $guidGenerator->newGuid( $newItem->getId() );
			$newItem->getStatements()->addNewStatement( $snak, null, null, $guid );

			$store->saveEntity( $newItem, '', $this->user, EDIT_UPDATE );
			$item = $newItem;
		}

		return $item;
	}

	public function provideAddRequests(): iterable {
		return [
			[ PropertyNoValueSnak::class ],
			[ PropertySomeValueSnak::class ],
			[ PropertyValueSnak::class, new StringValue( 'o_O' ) ],
		];
	}

	/**
	 * @dataProvider provideAddRequests
	 */
	public function testAddRequests( string $snakType, $data = null ) {
		$item = $this->getTestItem();
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$guid = $statement->getGuid();

		$snak = $this->getTestSnak( $snakType, $data );

		$this->makeSetQualifierRequest( $guid, null, $snak, $item->getId() );

		// now the hash exists, so the same request should fail
		$this->expectException( ApiUsageException::class );
		$this->makeSetQualifierRequest( $guid, null, $snak, $item->getId() );
	}

	public function testSetQualifierWithTag() {
		$item = $this->getTestItem();
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$guid = $statement->getGuid();

		$snak = $this->getTestSnak( PropertyValueSnak::class, new StringValue( 'o_O' ) );
		$newQualifier = new PropertyValueSnak( $snak->getPropertyId(), new StringValue( __METHOD__ ) );

		$this->assertCanTagSuccessfulRequest( [
			'action' => 'wbsetqualifier',
			'claim' => $guid,
			'snaktype' => $newQualifier->getType(),
			'property' => $newQualifier->getPropertyId()->getSerialization(),
			'value' => FormatJson::encode( $newQualifier->getDataValue()->getArrayValue() ),
		] );
	}

	public function testReturnsNormalizedData(): void {
		$propertyId = $this->createUppercaseStringTestProperty();
		$item = $this->getTestItem();
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$guid = $statement->getGuid();

		[ $response ] = $this->doApiRequestWithToken( [
			'action' => 'wbsetqualifier',
			'claim' => $guid,
			'snaktype' => 'value',
			'property' => $propertyId->getSerialization(),
			'value' => '"a string"',
		] );

		$responseSnak = $response['claim']['qualifiers'][$propertyId->getSerialization()][0];
		$this->assertSame( 'A STRING', $responseSnak['datavalue']['value'] );
	}

	public function provideChangeRequests(): iterable {
		return [ [ PropertyValueSnak::class, new StringValue( 'o_O' ) ] ];
	}

	/**
	 * @dataProvider provideChangeRequests
	 */
	public function testChangeRequests( string $snakType, $data ) {
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
		$this->expectException( ApiUsageException::class );
		$this->makeSetQualifierRequest( $guid, $hash, $newQualifier, $item->getId() );
	}

	protected function makeSetQualifierRequest( string $statementGuid, ?string $snakhash, Snak $qualifier, EntityId $entityId ): void {
		$params = [
			'action' => 'wbsetqualifier',
			'claim' => $statementGuid,
			'snakhash' => $snakhash,
			'snaktype' => $qualifier->getType(),
			'property' => $qualifier->getPropertyId()->getSerialization(),
		];

		if ( $qualifier instanceof PropertyValueSnak ) {
			$dataValue = $qualifier->getDataValue();
			$params['value'] = FormatJson::encode( $dataValue->getArrayValue() );
		}

		$this->makeValidRequest( $params );

		/** @var StatementListProvidingEntity $entity */
		$entity = WikibaseRepo::getEntityLookup()->getEntity( $entityId );

		$statements = $entity->getStatements();

		$statement = $statements->getFirstStatementWithGuid( $params['claim'] );

		$this->assertNotNull( $statement );
		$this->assertTrue(
			$statement->getQualifiers()->hasSnak( $qualifier ),
			'The qualifier should exist in the qualifier list after making the request'
		);
	}

	protected function makeValidRequest( array $params ): array {
		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertIsArray( $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'pageinfo', $resultArray, 'top level element has a pageinfo key' );
		$this->assertArrayHasKey( 'claim', $resultArray, 'top level element has a statement key' );

		return $resultArray;
	}

	/**
	 * @dataProvider invalidRequestProvider
	 */
	public function testInvalidRequest( string $itemHandle, ?string $guid, string $propertyHande, string $snakType, $value, $error ) {
		$itemId = new ItemId( EntityTestHelper::getId( $itemHandle ) );
		$item = WikibaseRepo::getEntityLookup()->getEntity( $itemId );

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

		$params = [
			'action' => 'wbsetqualifier',
			'claim' => $guid,
			'property' => $propertyId,
			'snaktype' => $snakType,
			'value' => $value,
		];

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Invalid request did not raise an error' );
		} catch ( ApiUsageException $ex ) {
			$msg = TestingAccessWrapper::newFromObject( $ex )->getApiMessage();
			$this->assertThat(
				$msg->getApiCode(),
				$error instanceof Constraint ? $error : $this->equalTo( $error ),
				'Invalid request raised correct error'
			);
		}
	}

	public function invalidRequestProvider(): iterable {
		return [
			'bad guid 1' => [ 'Berlin', 'xyz', 'StringProp', 'value', 'abc', 'invalid-guid' ],
			'bad guid 2' => [ 'Berlin', 'x$y$z', 'StringProp', 'value', 'abc', 'invalid-guid' ],
			'bad guid 3' => [ 'Berlin', 'i1813$358fa2a0-4345-82b6-12a4-7b0fee494a5f', 'StringProp', 'value', 'abc', 'invalid-guid' ],
			'bad snak type' => [ 'Berlin', null, 'StringProp', 'alksdjf', 'abc', $this->logicalOr(
				$this->equalTo( 'unknown_snaktype' ),
				$this->equalTo( 'badvalue' )
			) ],
			'bad snak value' => [ 'Berlin', null, 'StringProp', 'value', '"   "', 'modification-failed' ],
		];
	}

}
