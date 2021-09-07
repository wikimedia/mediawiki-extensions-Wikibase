<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\FederatedProperties\Api;

use Wikibase\DataModel\Entity\Property;

/**
 * @covers \Wikibase\Repo\Api\GetEntities
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
class GetEntitiesTest extends FederatedPropertiesApiTestCase {

	public function testGettingFederatedPropertiesShouldReturnError(): void {
		$fedPropId = $this->newFederatedPropertyIdFromPId( 'P1' );

		$params = [
			'action' => 'wbgetentities',
			'ids' => $fedPropId,
		];

		$this->setExpectedApiException( wfMessage( 'wikibase-federated-properties-federated-property-api-error-message' ) );
		$this->doApiRequestWithToken( $params );
	}

	public function testGetEntitiesWithLocalProperty(): void {
		$property = new Property( null, null, 'string' );
		$this->getEntityStore()->saveEntity( $property, 'feddypropstest', $this->user, EDIT_NEW );
		$id = $property->getId();

		[ $result ] = $this->doApiRequestWithToken( [
			'action' => 'wbgetentities',
			'ids' => $id,
		] );

		$this->assertArrayHasKey( 'success', $result );
		$this->assertArrayHasKey( $id->getSerialization(), $result['entities'] );
	}

}
