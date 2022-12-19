<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties\Api;

use DataValues\Serializers\DataValueSerializer;
use FormatJson;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;

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
 * @author Marius Hoch
 */
class SetClaimTest extends FederatedPropertiesApiTestCase {

	public function testAlteringFederatedPropertiesIsNotSupported() {
		$entity = new Property( $this->newFederatedPropertyIdFromPId( 'P123' ), null, 'string' );
		$entityId = $entity->getId();

		$statement = new Statement( new PropertyNoValueSnak( $this->newFederatedPropertyIdFromPId( 'P626' ) ) );
		$guidGenerator = new GuidGenerator();
		$guid = $guidGenerator->newGuid( $entityId );
		$statement->setGuid( $guid );

		$this->setExpectedApiException( wfMessage( 'wikibase-federated-properties-federated-property-api-error-message' ) );
		$this->doApiRequestWithToken( [
			'action' => 'wbsetclaim',
			'claim' => FormatJson::encode( $this->getSerializedStatement( $statement ) ),
		] );
	}

	public function testSetClaimOnLocalProperty(): void {
		$propertyForStatement = new Property( null, null, 'string' );
		$this->createLocalProperty( $propertyForStatement );

		$propertyUnderTest = new Property( null, null, 'string' );
		$this->createLocalProperty( $propertyUnderTest );

		$statement = new Statement( new PropertyNoValueSnak( $propertyForStatement->getId() ) );
		$statement->setGuid( ( new GuidGenerator() )->newGuid( $propertyUnderTest->getId() ) );

		[ $result ] = $this->doApiRequestWithToken( [
			'action' => 'wbsetclaim',
			'claim' => FormatJson::encode( $this->getSerializedStatement( $statement ) ),
		] );

		$this->assertArrayHasKey( 'success', $result );
	}

	public function testGivenSourceWikiUnavailable_respondsWithAnError() {
		$this->setSourceWikiUnavailable();

		$statement = new Statement( new PropertyNoValueSnak( $this->newFederatedPropertyIdFromPId( 'P626' ) ) );

		$entity = new Item();
		$this->getEntityStore()->saveEntity( $entity, 'setclaimtest', $this->user, EDIT_NEW );
		$entityId = $entity->getId();

		$guidGenerator = new GuidGenerator();
		$guid = $guidGenerator->newGuid( $entityId );
		$statement->setGuid( $guid );

		$this->setExpectedApiException( wfMessage( 'wikibase-federated-properties-save-api-error-message' ) );
		$this->doApiRequestWithToken( [
			'action' => 'wbsetclaim',
			'claim' => FormatJson::encode( $this->getSerializedStatement( $statement ) ),
		] );
	}

	public function testAddingStatementUsingFederatedProperty(): void {
		$fedPropRemoteId = 'P626';
		$fedPropId = $this->newFederatedPropertyIdFromPId( $fedPropRemoteId );
		$statement = new Statement( new PropertyNoValueSnak( $fedPropId ) );

		$entity = new Item();
		$this->getEntityStore()->saveEntity( $entity, 'setclaimtest', $this->user, EDIT_NEW );
		$entityId = $entity->getId();

		$statement->setGuid( ( new GuidGenerator() )->newGuid( $entityId ) );

		$this->mockSourceApiRequests( [
			[
				[
					'action' => 'wbgetentities',
					'ids' => $fedPropRemoteId,
				],
				[
					'entities' => [
						$fedPropRemoteId => [
							'datatype' => 'string',
						],
					],
				],
			],
			// The following request is made by the ConfirmEdit extension.
			[
				[
					'action' => 'query',
					'meta' => 'siteinfo',
					'siprop' => 'namespaces',
					'format' => 'json',
				],
				[
					'query' => [ 'namespaces' => [] ],
				],
			],
		] );

		[ $result ] = $this->doApiRequestWithToken( [
			'action' => 'wbsetclaim',
			'claim' => FormatJson::encode( $this->getSerializedStatement( $statement ) ),
		] );

		$this->assertArrayHasKey( 'success', $result );
		$this->assertSame(
			$fedPropId->getSerialization(),
			$result['claim']['mainsnak']['property']
		);
	}

	private function getSerializedStatement( $statement ): array {
		$statementSerializer = ( new SerializerFactory( new DataValueSerializer() ) )->newStatementSerializer();
		return $statementSerializer->serialize( $statement );
	}

	private function createLocalProperty( Property $prop ): void {
		$this->getEntityStore()->saveEntity( $prop, 'feddypropstest', $this->user, EDIT_NEW );
	}

}
