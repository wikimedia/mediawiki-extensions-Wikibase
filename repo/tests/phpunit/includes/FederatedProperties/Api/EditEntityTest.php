<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\FederatedProperties\Api;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Api\EditEntity
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Tobias Andersson
 */
class EditEntityTest extends FederatedPropertiesApiTestCase {

	public function testUpdatingAFederatedPropertyShouldFail(): void {
		$id = $this->newFederatedPropertyIdFromPId( 'P666' );

		$this->setExpectedApiException( wfMessage( 'wikibase-federated-properties-local-property-api-error-message' ) );

		$this->doApiRequestWithToken( [
			'action' => 'wbeditentity',
			'id' => $id->getSerialization(),
			'data' => json_encode( [
				'labels' => [
					'en' => [ 'language' => 'en', 'value' => 'im a feddy prop' ],
				],
			] ),
		] );
	}

	// We want updating local Properties to work eventually
	public function testUpdatingAPropertyShouldFail(): void {
		$entity = new Property( new PropertyId( 'P123' ), null, 'string' ); // needs to be saved via EntityStore
		$entityId = $entity->getId();

		$params = [
			'action' => 'wbeditentity',
			'id' => $entityId->getSerialization(),
			'data' => '{"datatype":"string"}'
		];

		$this->setExpectedApiException( wfMessage( 'wikibase-federated-properties-local-property-api-error-message' ) );
		$this->doApiRequestWithToken( $params );
	}

	public function testCreatingANewLocalProperty(): void {
		$expectedLabel = 'local prop';

		[ $result, ] = $this->doApiRequestWithToken( [
			'action' => 'wbeditentity',
			'new' => 'property',
			'data' => json_encode( [
				'datatype' => 'string',
				'labels' => [
					'en' => [ 'language' => 'en', 'value' => $expectedLabel ],
				],
			] ),
		] );

		$this->assertArrayHasKey(
			'success',
			$result
		);
		$this->assertSame(
			$expectedLabel,
			$result['entity']['labels']['en']['value']
		);
	}

	public function testAddStatementToLocalEntityContainingLocalProperty(): void {
		$store = WikibaseRepo::getEntityStore();

		$item = new Item( new ItemId( 'Q1' ) );
		$itemId = $item->getId();
		$store->saveEntity( $item, 'feddypropstest', $this->user, EDIT_NEW );

		$property = new Property( new PropertyId( 'P1' ), null, 'string' );
		$propertyId = $property->getId();
		$store->saveEntity( $property, 'feddypropstest', $this->user, EDIT_NEW );

		$statement = new Statement( new PropertyNoValueSnak( $propertyId ) );
		$guidGenerator = new GuidGenerator();
		$guid = $guidGenerator->newGuid( $itemId );
		$statement->setGuid( $guid );

		$statementSerializer = WikibaseRepo::getBaseDataModelSerializerFactory()->newStatementSerializer();
		$statementSerialization = $statementSerializer->serialize( $statement );

		$params = [
			'action' => 'wbeditentity',
			'id' => $itemId->getSerialization(),
			'data' => json_encode( [
				'claims' => [ $statementSerialization ],
			] ),
		];

		[ $result, ] = $this->doApiRequestWithToken( $params );

		$this->assertArrayHasKey(
			'success',
			$result
		);
		$this->assertSame(
			$propertyId->getSerialization(),
			$result['entity']['claims'][ $propertyId->getSerialization() ][ 0 ][ 'mainsnak' ][ 'property' ]
		);
	}

}
