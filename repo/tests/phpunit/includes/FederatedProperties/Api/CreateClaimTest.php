<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties\Api;

use Wikibase\DataModel\Entity\Property;

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
 * @author Tobias Andersson
 */
class CreateClaimTest extends FederatedPropertiesApiTestCase {

	public function testUpdatingAFederatedPropertyShouldFail(): void {
		$entity = new Property( $this->newFederatedPropertyIdFromPId( 'P123' ), null, 'string' );
		$entityId = $entity->getId();

		$params = [
			'action' => 'wbcreateclaim',
			'entity' => $entityId->getSerialization(),
			'snaktype' => 'novalue',
			'property' => $entityId->getSerialization(),
		];

		$this->setExpectedApiException( wfMessage( 'wikibase-federated-properties-federated-property-api-error-message' ) );
		$this->doApiRequestWithToken( $params );
	}

	public function testCreateClaimForLocalProperty(): void {
		$property = new Property( null, null, 'string' );
		$this->getEntityStore()->saveEntity( $property, 'feddypropstest', $this->user, EDIT_NEW );
		$id = $property->getId();

		[ $result ] = $this->doApiRequestWithToken( [
			'action' => 'wbcreateclaim',
			'entity' => $id->getSerialization(),
			'snaktype' => 'novalue',
			'property' => $id->getSerialization(),
		] );

		$this->assertArrayHasKey( 'success', $result );
		$this->assertStringStartsWith( $id->getSerialization(), $result['claim']['id'] );
	}

}
