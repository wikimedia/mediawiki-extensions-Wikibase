<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\FederatedProperties\Api;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Tests\NewStatement;

/**
 * @covers \Wikibase\Repo\Api\RemoveQualifiers
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
class RemoveQualifiersTest extends FederatedPropertiesApiTestCase {

	public function testUpdatingAFederatedPropertyShouldFail() {
		$entity = new Property( $this->newFederatedPropertyIdFromPId( 'P123' ), null, 'string' );

		$statement = new Statement( new PropertyValueSnak( $entity->getId(), new StringValue( 'O_ö' ) ) );

		$snaks = new SnakList( [ new PropertyNoValueSnak( 42 ) ] );
		$statement->setQualifiers( $snaks );

		$guidGenerator = new GuidGenerator();
		$statement->setGuid( $guidGenerator->newGuid( $entity->getId() ) );
		$entity->getStatements()->addStatement( $statement );

		$hashes = array_map(
			function( Snak $qualifier ) {
				return $qualifier->getHash();
			},
			iterator_to_array( $statement->getQualifiers() )
		);

		$params = [
			'action' => 'wbremovequalifiers',
			'claim' => $statement->getGuid(),
			'qualifiers' => implode( '|', $hashes ),
		];

		$this->setExpectedApiException( wfMessage( 'wikibase-federated-properties-federated-property-api-error-message' ) );
		$this->doApiRequestWithToken( $params );
	}

	public function testRemoveQualifierFromLocalPropertyStatement(): void {
		$propertyForStatementAndQualifier = new Property( null, null, 'string' );
		$this->saveLocalProperty( $propertyForStatementAndQualifier );

		$propertyUnderTest = new Property( null, null, 'string' );
		$this->saveLocalProperty( $propertyUnderTest );

		$statementWithQualifier = NewStatement::noValueFor( $propertyForStatementAndQualifier->getId() )
			->withGuid( ( new GuidGenerator() )->newGuid( $propertyUnderTest->getId() ) )
			->withQualifier( $propertyForStatementAndQualifier->getId(), 'imma be removed' )
			->build();
		$propertyUnderTest->setStatements( new StatementList( $statementWithQualifier ) );
		$this->saveLocalProperty( $propertyUnderTest );

		[ $result ] = $this->doApiRequestWithToken( [
			'action' => 'wbremovequalifiers',
			'claim' => $statementWithQualifier->getGuid(),
			'qualifiers' => $statementWithQualifier->getQualifiers()[0]->getHash(),
		] );

		$this->assertArrayHasKey( 'success', $result );
	}

	private function saveLocalProperty( Property $prop ): void {
		$this->getEntityStore()->saveEntity( $prop, 'feddypropstest', $this->user, $prop->getId() ? EDIT_UPDATE : EDIT_NEW );
	}

}
