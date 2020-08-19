<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\FederatedProperties\Api;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;

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

	public function testUpdatingAPropertyShouldFail() {
		$entity = new Property( new PropertyId( 'P123' ), null, 'string' );
		$entityId = $entity->getId();

		$params = [
			'action' => 'wbeditentity',
			'id' => $entityId->getSerialization(),
			'data' => '{"datatype":"string"}'
		];

		$this->setExpectedApiException( wfMessage( 'wikibase-federated-properties-local-property-api-error-message' ) );
		$this->doApiRequestWithToken( $params );
	}

	public function testCreatingANewPropertyShouldFailFromMediawikiParamValidator() {

		$params = [
			'action' => 'wbeditentity',
			'new' => 'property',
			'data' => '{"datatype":"string"}'
		];

		$this->setExpectedApiException( wfMessage( 'apierror-unrecognizedvalue', 'new', 'property' ) );
		$this->doApiRequestWithToken( $params );
	}
}
