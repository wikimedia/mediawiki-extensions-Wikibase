<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\FederatedProperties\Api;

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

	public function testGettingPropertiesShouldReturnError() {
		$params = [
			'action' => 'wbgetentities',
			'ids' => 'P1|P2|Q1',
		];

		$this->setExpectedApiException( wfMessage( 'wikibase-federated-properties-local-property-api-error-message' ) );
		$this->doApiRequestWithToken( $params );
	}
}
