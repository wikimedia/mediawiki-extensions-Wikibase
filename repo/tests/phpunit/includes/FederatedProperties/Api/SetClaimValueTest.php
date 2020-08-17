<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\FederatedProperties\Api;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;

/**
 * @covers \Wikibase\Repo\Api\SetClaimValue
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
class SetClaimValueTest extends FederatedPropertiesApiTestCase {

	public function testUpdatingAPropertyShouldFail() {
		$entity = new Property( new PropertyId( 'P123' ), null, 'string' );
		$entityId = $entity->getId();

		$guidGenerator = new GuidGenerator();
		$guid = $guidGenerator->newGuid( $entityId );

		$params = [
			'action' => 'wbsetclaimvalue',
			'claim' => $guid,
			'value' => '{}',
			'snaktype' => 'value',
		];

		$this->setExpectedApiException( wfMessage( 'wikibase-federated-properties-local-property-api-error-message' ) );
		$this->doApiRequestWithToken( $params );
	}
}
