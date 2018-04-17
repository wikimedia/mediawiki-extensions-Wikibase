<?php

namespace Wikibase\Client\Tests;

use HashSiteStore;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataAccess\MultipleRepositoryAwareWikibaseServices;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\RepositoryDefinitions;
use Wikibase\SettingsArray;

/**
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0-or-later
 */
class MultiRepositoryServicesIntegrationTest extends \MediaWikiTestCase {

	public function testExtensionCanRegisterCustomMultiRepositoryServices() {
		$this->setMwGlobals( [
			'wgWikibaseMultiRepositoryServiceWiringFiles' => [ __DIR__ . '/MultiRepositoryServiceTestWiring.php' ],
			'wgWikibasePerRepositoryServiceWiringFiles' => [ __DIR__ . '/PerRepositoryServiceTestWiring.php' ],
		] );

		$client = $this->getWikibaseClient();
		$services = $client->getWikibaseServices();
		// UGLY: this relies on implementation details!
		/** @var MultipleRepositoryAwareWikibaseServices $services */
		$multiRepoServices = $services->getMultiRepositoryServices();

		$this->assertContains( 'AwesomeService', $multiRepoServices->getServiceNames() );
	}

	private function getWikibaseClient() {
		return new WikibaseClient(
			new SettingsArray( WikibaseClient::getDefaultInstance()->getSettings()->getArrayCopy() ),
			new DataTypeDefinitions( [] ),
			new EntityTypeDefinitions( [] ),
			new RepositoryDefinitions(
				[ '' => [ 'database' => '', 'base-uri' => '', 'entity-namespaces' => [], 'prefix-mapping' => [] ] ],
				new EntityTypeDefinitions( [] )
			),
			new HashSiteStore()
		);
	}

}
