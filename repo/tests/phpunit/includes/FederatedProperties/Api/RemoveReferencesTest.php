<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\FederatedProperties\Api;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Tests\NewStatement;

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

	public function testUpdatingAFederatedPropertyShouldFail() {
		$entity = new Property( $this->newFederatedPropertyIdFromPId( 'P123' ), null, 'string' );

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

		$this->setExpectedApiException( wfMessage( 'wikibase-federated-properties-federated-property-api-error-message' ) );
		$this->doApiRequestWithToken( $params );
	}

	public function testRemoveReferenceFromLocalPropertyStatement(): void {
		$propertyForStatementAndReference = new Property( null, null, 'string' );
		$this->saveLocalProperty( $propertyForStatementAndReference );

		$propertyUnderTest = new Property( null, null, 'string' );
		$this->saveLocalProperty( $propertyUnderTest );

		$statementWithReference = NewStatement::noValueFor( $propertyForStatementAndReference->getId() )
			->withGuid( ( new GuidGenerator() )->newGuid( $propertyUnderTest->getId() ) )
			->build();
		$statementWithReference->addNewReference( new PropertySomeValueSnak( $propertyForStatementAndReference->getId() ) );
		$propertyUnderTest->setStatements( new StatementList( $statementWithReference ) );
		$this->saveLocalProperty( $propertyUnderTest );

		[ $result ] = $this->doApiRequestWithToken( [
			'action' => 'wbremovereferences',
			'statement' => $statementWithReference->getGuid(),
			'references' => $statementWithReference->getReferences()->getIterator()[0]->getHash(),
		] );

		$this->assertArrayHasKey( 'success', $result );
	}

	private function saveLocalProperty( Property $prop ): void {
		$this->getEntityStore()->saveEntity( $prop, 'feddypropstest', $this->user, $prop->getId() ? EDIT_UPDATE : EDIT_NEW );
	}

}
