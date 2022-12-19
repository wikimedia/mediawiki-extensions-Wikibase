<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\FederatedProperties\Api;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Tests\NewStatement;

/**
 * @covers \Wikibase\Repo\Api\SetReference
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
class SetReferenceTest extends FederatedPropertiesApiTestCase {

	public function testUpdatingAFederatedPropertyShouldFail() {
		$entity = new Property( $this->newFederatedPropertyIdFromPId( 'P123' ), null, 'string' );

		$mainSnak = new PropertyNoValueSnak( 42 );
		$statement = new Statement( $mainSnak );

		$guidGenerator = new GuidGenerator();
		$statement->setGuid( $guidGenerator->newGuid( $entity->getId() ) );
		$entity->getStatements()->addStatement( $statement );

		$params = [
			'action' => 'wbsetreference',
			'statement' => $statement->getGuid(),
			'snaks' => '{}',
		];

		$this->setExpectedApiException( wfMessage( 'wikibase-federated-properties-federated-property-api-error-message' ) );
		$this->doApiRequestWithToken( $params );
	}

	public function testSetReferenceOnLocalPropertyStatement(): void {
		$propertyForStatementAndReference = new Property( null, null, 'string' );
		$this->saveLocalProperty( $propertyForStatementAndReference );
		$propertyIdForStatementAndReference = $propertyForStatementAndReference->getId()->getSerialization();

		$propertyUnderTest = new Property( null, null, 'string' );
		$this->saveLocalProperty( $propertyUnderTest );

		$statementToChange = NewStatement::noValueFor( $propertyForStatementAndReference->getId() )
			->withGuid( ( new GuidGenerator() )->newGuid( $propertyUnderTest->getId() ) )
			->build();
		$propertyUnderTest->setStatements( new StatementList( $statementToChange ) );
		$this->saveLocalProperty( $propertyUnderTest );

		[ $result ] = $this->doApiRequestWithToken( [
			'action' => 'wbsetreference',
			'statement' => $statementToChange->getGuid(),
			'snaks' => json_encode( [
				$propertyIdForStatementAndReference => [
					[
						'snaktype' => 'value',
						'property' => $propertyIdForStatementAndReference,
						'datavalue' => [ 'type' => 'string', 'value' => 'potato' ],
					],
				],
			] ),
		] );

		$this->assertArrayHasKey( 'success', $result );
		$this->assertArrayHasKey( $propertyIdForStatementAndReference, $result['reference']['snaks'] );
	}

	private function saveLocalProperty( Property $prop ): void {
		$this->getEntityStore()->saveEntity( $prop, 'feddypropstest', $this->user, $prop->getId() ? EDIT_UPDATE : EDIT_NEW );
	}

}
