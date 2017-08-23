<?php

namespace Wikibase\Client\Tests\Store\Sql;

use Wikibase\Client\RecentChanges\RecentChangesDuplicateDetector;
use Wikibase\Client\Usage\SubscriptionManager;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Client\Usage\UsageTracker;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataAccess\WikibaseServices;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Entity\NullEntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;
use Wikibase\Client\Store\Sql\DirectSqlStore;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Tests\Store\MockPropertyInfoLookup;
use Wikibase\Store\EntityIdLookup;
use Wikibase\TermIndex;
use Wikibase\WikibaseSettings;

/**
 * @covers Wikibase\Client\Store\Sql\DirectSqlStore
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class DirectSqlStoreTest extends \MediaWikiTestCase {

	protected function newStore() {
		$entityChangeFactory = $this->getMockBuilder( EntityChangeFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$client = WikibaseClient::getDefaultInstance();

		$wikibaseServices = $this->getMock( WikibaseServices::class );

		$wikibaseServices->method( 'getEntityPrefetcher' )
			->willReturn( new NullEntityPrefetcher() );
		$wikibaseServices->method( 'getEntityRevisionLookup' )
			->willReturn( $this->getMock( EntityRevisionLookup::class ) );
		$wikibaseServices->method( 'getPropertyInfoLookup' )
			->willReturn( new MockPropertyInfoLookup() );

		return new DirectSqlStore(
			$entityChangeFactory,
			new ItemIdParser(),
			new EntityIdComposer( [] ),
			new EntityNamespaceLookup( [] ),
			$wikibaseServices,
			wfWikiID(),
			'en'
		);
	}

	/**
	 * @dataProvider provideGetters
	 */
	public function testGetters( $getter, $expectedType ) {
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
			[ 'getPropertyInfoLookup', PropertyInfoLookup::class ],
			[ 'getUsageTracker', UsageTracker::class ],
			[ 'getUsageLookup', UsageLookup::class ],
			[ 'getEntityIdLookup', EntityIdLookup::class ],
			[ 'getEntityPrefetcher', EntityPrefetcher::class ],
			[ 'getEntityChangeLookup', EntityChangeLookup::class ],
			[ 'getRecentChangesDuplicateDetector', RecentChangesDuplicateDetector::class ],
		];
	}

	public function testGetSubscriptionManager() {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( 'getSubscriptionManager needs the repository extension to be active.' );
		}

		$store = $this->newStore();

		$this->assertInstanceOf( SubscriptionManager::class, $store->getSubscriptionManager() );
	}

}
