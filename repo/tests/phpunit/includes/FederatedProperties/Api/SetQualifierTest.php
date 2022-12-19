<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\FederatedProperties\Api;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Tests\NewStatement;

/**
 * @covers \Wikibase\Repo\Api\SetQualifier
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
class SetQualifierTest extends FederatedPropertiesApiTestCase {

	public function testUpdatingAFederatedPropertyShouldFail(): void {
		$entity = new Property( $this->newFederatedPropertyIdFromPId( 'P123' ), null, 'string' );
		$entityId = $entity->getId();

		$guidGenerator = new GuidGenerator();
		$guid = $guidGenerator->newGuid( $entityId );

		$params = [
			'action' => 'wbsetqualifier',
			'claim' => $guid,
			'snaktype' => 'value',
			'property' => 'something cool',
			'value' => 'mega cool!',
		];

		$this->setExpectedApiException( wfMessage( 'wikibase-federated-properties-federated-property-api-error-message' ) );
		$this->doApiRequestWithToken( $params );
	}

	public function testSetQualifierOnLocalPropertyStatement(): void {
		$propertyForStatementAndQualifier = new Property( null, null, 'string' );
		$this->saveLocalProperty( $propertyForStatementAndQualifier );

		$propertyUnderTest = new Property( null, null, 'string' );
		$this->saveLocalProperty( $propertyUnderTest );

		$statementToChange = NewStatement::noValueFor( $propertyForStatementAndQualifier->getId() )
			->withGuid( ( new GuidGenerator() )->newGuid( $propertyUnderTest->getId() ) )
			->build();
		$propertyUnderTest->setStatements( new StatementList( $statementToChange ) );
		$this->saveLocalProperty( $propertyUnderTest );

		[ $result ] = $this->doApiRequestWithToken( [
			'action' => 'wbsetqualifier',
			'snaktype' => 'novalue',
			'claim' => $statementToChange->getGuid(),
			'property' => $propertyForStatementAndQualifier->getId(),
		] );

		$this->assertArrayHasKey( 'success', $result );
		$this->assertArrayHasKey( $propertyForStatementAndQualifier->getId()->getSerialization(), $result['claim']['qualifiers'] );
	}

	private function saveLocalProperty( Property $prop ): void {
		$this->getEntityStore()->saveEntity( $prop, 'feddypropstest', $this->user, $prop->getId() ? EDIT_UPDATE : EDIT_NEW );
	}

}
