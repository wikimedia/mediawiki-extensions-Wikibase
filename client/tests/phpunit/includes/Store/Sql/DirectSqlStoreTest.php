<?php

namespace Wikibase\Client\Tests\Store\Sql;

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
use Wikibase\Lib\Store\EntityChangeLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\PropertyInfoStore;
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

		$contentCodec = WikibaseClient::getDefaultInstance()->getEntityContentDataCodec();

		$entityNamespaceLookup = new EntityNamespaceLookup( [] );

		$store = new DirectSqlStore(
			$entityChangeFactory,
			$contentCodec,
			$idParser,
			$entityNamespaceLookup,
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
		return [
			[ 'getSiteLinkLookup', SiteLinkLookup::class ],
			[ 'getEntityLookup', EntityLookup::class ],
			[ 'getTermIndex', TermIndex::class ],
			[ 'getPropertyLabelResolver', PropertyLabelResolver::class ],
			[ 'getPropertyInfoStore', PropertyInfoStore::class ],
			[ 'getUsageTracker', UsageTracker::class ],
			[ 'getUsageLookup', UsageLookup::class ],
			[ 'getSubscriptionManager', SubscriptionManager::class, true ],
			[ 'getEntityIdLookup', EntityIdLookup::class ],
			[ 'getEntityPrefetcher', EntityPrefetcher::class ],
			[ 'getEntityChangeLookup', EntityChangeLookup::class ],
			[ 'getRecentChangesDuplicateDetector', RecentChangesDuplicateDetector::class ],
		];
	}

}
