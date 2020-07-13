<?php

namespace Wikibase\Repo\Tests\Api;

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
class SetClaimFederatedPropertiesTest extends WikibaseApiTestCase {

	/**
	 * @var bool|null
	 */
	private $oldFederatedPropertiesEnabled;

	protected function setUp(): void {
		parent::setUp();

		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		$this->oldFederatedPropertiesEnabled = $settings->getSetting( 'federatedPropertiesEnabled' );
		$settings->setSetting( 'federatedPropertiesEnabled', true );
	}

	protected function tearDown(): void {
		parent::tearDown();

		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		$settings->setSetting( 'federatedPropertiesEnabled', $this->oldFederatedPropertiesEnabled );
	}

	public function testFederatedPropertiesFailure() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$settings = $wikibaseRepo->getSettings();

		$oldFederatedPropertiesSourceScriptUrl = $settings->getSetting( 'federatedPropertiesSourceScriptUrl' );
		// Make sure federated properties can't work.
		$settings->setSetting( 'federatedPropertiesSourceScriptUrl', '255.255.255.255/' );

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

		$settings->setSetting( 'federatedPropertiesSourceScriptUrl', $oldFederatedPropertiesSourceScriptUrl );
	}

}
