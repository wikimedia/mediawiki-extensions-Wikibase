<?php

namespace Wikibase\Client\Tests\Store\Sql;

use Wikibase\Client\DispatchingServiceFactory;
use Wikibase\Client\RecentChanges\RecentChangesDuplicateDetector;
use Wikibase\Client\Usage\SubscriptionManager;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Client\Usage\UsageTracker;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;
use Wikibase\DirectSqlStore;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\Store\EntityChangeLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Tests\Store\MockPropertyInfoLookup;
use Wikibase\Store\EntityIdLookup;
use Wikibase\TermIndex;

/**
 * @covers Wikibase\DirectSqlStore
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseClientStore
 *
 * @license GPL-2.0+
 * @author DanielKinzler
 */
class DirectSqlStoreTest extends \MediaWikiTestCase {

	protected function newStore() {

		$entityChangeFactory = $this->getMockBuilder( EntityChangeFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$idParser = new BasicEntityIdParser();
		$idComposer = new EntityIdComposer( [] );

		$client = WikibaseClient::getDefaultInstance();

		$contentCodec = $client->getEntityContentDataCodec();

		$entityNamespaceLookup = new EntityNamespaceLookup( [] );

		$dispatchingServiceFactory = new DispatchingServiceFactory( $client );
		$dispatchingServiceFactory->defineService( 'EntityRevisionLookup', function() {
			return $this->getMock( EntityRevisionLookup::class );
		} );
		$dispatchingServiceFactory->defineService( 'PropertyInfoLookup', function() {
			return new MockPropertyInfoLookup();
		} );

		$store = new DirectSqlStore(
			$entityChangeFactory,
			$contentCodec,
			$idParser,
			$idComposer,
			$entityNamespaceLookup,
			$dispatchingServiceFactory,
			wfWikiID(),
			'en'
		);

		return $store;
	}

	/**
	 * @dataProvider provideGetters
	 */
	public function testGetters( $getter, $expectedType, $needsLocalRepo = false ) {
		if ( $needsLocalRepo && !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( $getter . ' needs the repository extension to be active.' );
		}

		$store = $this->newStore();

		$this->assertTrue( method_exists( $store, $getter ), "Method $getter" );

		$obj = $store->$getter();

		$this->assertInstanceOf( $expectedType, $obj );
	}

	public function provideGetters() {
		return array(
			array( 'getSiteLinkLookup', SiteLinkLookup::class ),
			array( 'getEntityLookup', EntityLookup::class ),
			array( 'getTermIndex', TermIndex::class ),
			array( 'getPropertyLabelResolver', PropertyLabelResolver::class ),
			array( 'getPropertyInfoLookup', PropertyInfoLookup::class ),
			array( 'getUsageTracker', UsageTracker::class ),
			array( 'getUsageLookup', UsageLookup::class ),
			array( 'getSubscriptionManager', SubscriptionManager::class, true ),
			array( 'getEntityIdLookup', EntityIdLookup::class ),
			array( 'getEntityPrefetcher', EntityPrefetcher::class ),
			array( 'getEntityChangeLookup', EntityChangeLookup::class ),
			array( 'getRecentChangesDuplicateDetector', RecentChangesDuplicateDetector::class ),
		);
	}

}
