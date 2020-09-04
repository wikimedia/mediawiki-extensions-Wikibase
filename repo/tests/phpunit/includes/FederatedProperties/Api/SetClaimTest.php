<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties\Api;

use DataValues\Serializers\DataValueSerializer;
use FormatJson;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\WikibaseRepo;

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

	public function testAlteringPropertiesIsNotSupported() {
		$entity = new Property( new PropertyId( 'P123' ), null, 'string' );
		$entityId = $entity->getId();

		$statement = new Statement( new PropertyNoValueSnak( new PropertyId( 'P626' ) ) );
		$guidGenerator = new GuidGenerator();
		$guid = $guidGenerator->newGuid( $entityId );
		$statement->setGuid( $guid );

		$this->setExpectedApiException( wfMessage( 'wikibase-federated-properties-local-property-api-error-message' ) );
		$this->doApiRequestWithToken( [
			'action' => 'wbsetclaim',
			'claim' => FormatJson::encode( $this->getSerializedStatement( $statement ) ),
		] );
	}

	public function testFederatedPropertiesFailure() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$this->setSourceWikiUnavailable();

		$store = $wikibaseRepo->getEntityStore();

		$statement = new Statement( new PropertyNoValueSnak( new PropertyId( 'P626' ) ) );

		$entity = new Item();
		$store->saveEntity( $entity, 'setclaimtest', $this->user, EDIT_NEW );
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

	private function getSerializedStatement( $statement ) {
		$statementSerializer = ( new SerializerFactory( new DataValueSerializer() ) )->newStatementSerializer();
		return $statementSerializer->serialize( $statement );
	}

}
