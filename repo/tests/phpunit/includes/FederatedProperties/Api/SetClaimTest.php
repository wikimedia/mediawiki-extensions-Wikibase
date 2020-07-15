<?php

namespace Wikibase\Repo\Tests\FederatedProperties\Api;

use DataValues\Serializers\DataValueSerializer;
use FormatJson;
use Wikibase\DataModel\Entity\Item;
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

		$statementSerializer = ( new SerializerFactory( new DataValueSerializer() ) )->newStatementSerializer();
		$serialized = $statementSerializer->serialize( $statement );

		$this->setExpectedApiException( wfMessage( 'wikibase-federated-properties-save-api-error-message' ) );
		$this->doApiRequestWithToken( [
			'action' => 'wbsetclaim',
			'claim' => FormatJson::encode( $serialized ),
		] );
	}

}
