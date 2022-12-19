<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\FederatedProperties\Api;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
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

		$this->setExpectedApiException( wfMessage( 'wikibase-federated-properties-federated-property-api-error-message' ) );

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

	public function testEditEntityForLocalProperty(): void {
		$property = new Property( null, null, 'string' );
		$this->getEntityStore()->saveEntity( $property, 'feddypropstest', $this->user, EDIT_NEW );
		$id = $property->getId();

		$label = 'im a local prop';
		$labelLanguage = 'en';
		[ $result ] = $this->doApiRequestWithToken( [
			'action' => 'wbeditentity',
			'id' => $id->getSerialization(),
			'data' => json_encode( [
				'labels' => [
					$labelLanguage => [ 'language' => $labelLanguage, 'value' => $label ],
				],
			] ),
		] );

		$this->assertArrayHasKey( 'success', $result );
		$this->assertSame( $label, $result['entity']['labels'][$labelLanguage]['value'] );
	}

	public function testCreatingANewLocalProperty(): void {
		$expectedLabel = 'local prop';

		[ $result ] = $this->doApiRequestWithToken( [
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

		$item = new Item();
		$store->saveEntity( $item, 'feddypropstest', $this->user, EDIT_NEW );
		$itemId = $item->getId();

		$property = new Property( null, null, 'string' );
		$store->saveEntity( $property, 'feddypropstest', $this->user, EDIT_NEW );
		$propertyId = $property->getId();

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

		[ $result ] = $this->doApiRequestWithToken( $params );

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
