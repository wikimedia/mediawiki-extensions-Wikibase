<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\FederatedProperties\Api;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers \Wikibase\Repo\Api\RemoveReferences
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
class RemoveReferencesTest extends FederatedPropertiesApiTestCase {

	public function testUpdatingAPropertyShouldFail() {
		$entity = new Property( new PropertyId( 'P123' ), null, 'string' );

		$mainSnak = new PropertyNoValueSnak( 42 );
		$statement = new Statement( $mainSnak );

		$guidGenerator = new GuidGenerator();
		$statement->setGuid( $guidGenerator->newGuid( $entity->getId() ) );
		$entity->getStatements()->addStatement( $statement );

		$params = [
			'action' => 'wbremovereferences',
			'statement' => $statement->getGuid(),
			'references' => 'some hash',
		];

		$this->setExpectedApiException( wfMessage( 'wikibase-federated-properties-local-property-api-error-message' ) );
		$this->doApiRequestWithToken( $params );
	}
}
