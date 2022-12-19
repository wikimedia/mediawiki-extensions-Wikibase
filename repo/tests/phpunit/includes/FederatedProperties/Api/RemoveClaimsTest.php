<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties\Api;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Tests\NewStatement;

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

	public function testUpdatingAFederatedPropertyShouldFail(): void {
		$entity = new Property( $this->newFederatedPropertyIdFromPId( 'P123' ), null, 'string' );

		/** @var Statement[] $statements */
		$statements = [
			new Statement( new PropertyNoValueSnak( $entity->getId() ) ),
			new Statement( new PropertySomeValueSnak( $entity->getId() ) ),
			new Statement( new PropertyValueSnak( $entity->getId(), new StringValue( '^_^' ) ) ),
		];

		$guidGenerator = new GuidGenerator();
		$entityStatements = $entity->getStatements();
		foreach ( $statements as $statement ) {
			$statement->setGuid( $guidGenerator->newGuid( $entity->getId() ) );
			$entityStatements->addStatement( $statement );
		}

		$params = [
			'action' => 'wbremoveclaims',
			'claim' => implode( '|', array_map( function( $statement ) {
				return $statement->getGuid();
			}, $statements ) ),
		];

		$this->setExpectedApiException( wfMessage( 'wikibase-federated-properties-federated-property-api-error-message' ) );
		$this->doApiRequestWithToken( $params );
	}

	public function testRemoveClaimFromLocalProperty(): void {
		$propertyForStatement = new Property( null, null, 'string' );
		$this->saveLocalProperty( $propertyForStatement );

		$propertyUnderTest = new Property( null, null, 'string' );
		$this->saveLocalProperty( $propertyUnderTest );

		$statementToRemove = NewStatement::noValueFor( $propertyForStatement->getId() )
			->withGuid( ( new GuidGenerator() )->newGuid( $propertyUnderTest->getId() ) )
			->build();
		$propertyUnderTest->setStatements( new StatementList( $statementToRemove ) );
		$this->saveLocalProperty( $propertyUnderTest );

		[ $result ] = $this->doApiRequestWithToken( [
			'action' => 'wbremoveclaims',
			'claim' => $statementToRemove->getGuid(),
		] );

		$this->assertArrayHasKey( 'success', $result );
	}

	private function saveLocalProperty( Property $prop ): void {
		$this->getEntityStore()->saveEntity( $prop, 'feddypropstest', $this->user, $prop->getId() ? EDIT_UPDATE : EDIT_NEW );
	}

}
