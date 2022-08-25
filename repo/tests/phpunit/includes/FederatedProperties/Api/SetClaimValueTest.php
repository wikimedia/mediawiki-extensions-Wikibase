<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\FederatedProperties\Api;

use DataValues\StringValue;
use FormatJson;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Tests\NewStatement;

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

	public function testUpdatingAFederatedPropertyShouldFail(): void {
		$entityId = $this->newFederatedPropertyIdFromPId( 'P123' );

		$guidGenerator = new GuidGenerator();
		$guid = $guidGenerator->newGuid( $entityId );

		$params = [
			'action' => 'wbsetclaimvalue',
			'claim' => $guid,
			'value' => '{}',
			'snaktype' => 'value',
		];

		$this->setExpectedApiException( wfMessage( 'wikibase-federated-properties-federated-property-api-error-message' ) );
		$this->doApiRequestWithToken( $params );
	}

	public function testSetClaimValueOnLocalProperty(): void {
		$propertyForStatement = new Property( null, null, 'string' );
		$this->saveLocalProperty( $propertyForStatement );

		$propertyUnderTest = new Property( null, null, 'string' );
		$this->saveLocalProperty( $propertyUnderTest );

		$statementToChange = NewStatement::noValueFor( $propertyForStatement->getId() )
			->withGuid( ( new GuidGenerator() )->newGuid( $propertyUnderTest->getId() ) )
			->build();
		$propertyUnderTest->setStatements( new StatementList( $statementToChange ) );
		$this->saveLocalProperty( $propertyUnderTest );

		$expectedValue = 'potato';
		[ $result ] = $this->doApiRequestWithToken( [
			'action' => 'wbsetclaimvalue',
			'claim' => $statementToChange->getGuid(),
			'value' => FormatJson::encode( ( new StringValue( $expectedValue ) )->getArrayValue() ),
			'snaktype' => 'value',
		] );

		$this->assertArrayHasKey( 'success', $result );
		$this->assertSame( $expectedValue, $result['claim']['mainsnak']['datavalue']['value'] );
	}

	private function saveLocalProperty( Property $prop ): void {
		$this->getEntityStore()->saveEntity( $prop, 'feddypropstest', $this->user, $prop->getId() ? EDIT_UPDATE : EDIT_NEW );
	}

}
