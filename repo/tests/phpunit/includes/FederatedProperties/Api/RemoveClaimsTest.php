<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties\Api;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers \Wikibase\Repo\Api\RemoveClaims
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
class RemoveClaimsTest extends FederatedPropertiesApiTestCase {

	public function testFederatedPropertiesFailure() {

		$entity = new Property( new PropertyId( 'P123' ), null, 'string' );

		/** @var Statement[] $statements */
		$statements = [
			new Statement( new PropertyNoValueSnak( $entity->getId() ) ),
			new Statement( new PropertySomeValueSnak( $entity->getId() ) ),
			new Statement( new PropertyValueSnak( $entity->getId(), new StringValue( '^_^' ) ) ),
		];

		foreach ( $statements as $statement ) {
			$guidGenerator = new GuidGenerator();
			$statement->setGuid( $guidGenerator->newGuid( $entity->getId() ) );
			$entity->getStatements()->addStatement( $statement );
		}

		$params = [
			'action' => 'wbremoveclaims',
			'claim' => implode( '|', array_map( function( $statement ) {
				return $statement->getGuid();
			}, $statements ) )
		];

		$this->setExpectedApiException( wfMessage( 'wikibase-federated-properties-local-property-api-error-message' ) );
		$this->doApiRequestWithToken( $params );
	}

}
