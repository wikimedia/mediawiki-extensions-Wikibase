<?php

namespace Wikibase\Repo\Tests\FederatedProperties\Api;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\WikibaseRepo;

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

	public function testFederatedPropertiesFailure() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$store = $wikibaseRepo->getEntityStore();

		$entity = new Property( new PropertyId( 'P123' ), null, 'string' );
		$entityId = $entity->getId();

		$params = [
			'action' => 'wbcreateclaim',
			'entity' => $entityId->getSerialization(),
			'snaktype' => 'novalue',
			'property' => $entityId->getSerialization(),
		];

		$this->setExpectedApiException( wfMessage( 'wikibase-federated-properties-local-property-api-error-message' ) );
		$this->doApiRequestWithToken( $params );
	}

}
