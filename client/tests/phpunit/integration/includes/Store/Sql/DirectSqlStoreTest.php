<?php

namespace Wikibase\Client\Tests\Integration\Store\Sql;

use MediaWikiIntegrationTestCase;
use Wikibase\Client\RecentChanges\RecentChangesFinder;
use Wikibase\Client\Store\Sql\DirectSqlStore;
use Wikibase\Client\Usage\ImplicitDescriptionUsageLookup;
use Wikibase\Client\Usage\SubscriptionManager;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Client\Usage\UsageTracker;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataAccess\WikibaseServices;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Entity\NullEntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\Rdbms\ClientDomainDb;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Tests\Rdbms\LocalRepoDbTestHelper;
use Wikibase\Lib\Tests\Store\MockPropertyInfoLookup;
use Wikibase\Lib\WikibaseSettings;
use Wikimedia\Rdbms\LBFactorySingle;

/**
 * @covers \Wikibase\Client\Store\Sql\DirectSqlStore
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class DirectSqlStoreTest extends MediaWikiIntegrationTestCase {

	use LocalRepoDbTestHelper;

	protected function newStore() {
		$wikibaseServices = $this->createMock( WikibaseServices::class );

		$wikibaseServices->method( 'getEntityPrefetcher' )
			->willReturn( new NullEntityPrefetcher() );
		$wikibaseServices->method( 'getEntityRevisionLookup' )
			->willReturn( $this->createMock( EntityRevisionLookup::class ) );
		$wikibaseServices->method( 'getPropertyInfoLookup' )
			->willReturn( new MockPropertyInfoLookup() );

		return new DirectSqlStore(
			new ItemIdParser(),
			$this->createMock( EntityIdLookup::class ),
			$wikibaseServices,
			WikibaseClient::getSettings(),
			$this->createMock( TermBuffer::class ),
			$this->getRepoDomainDb(),
			new ClientDomainDb(
				LBFactorySingle::newFromConnection( $this->db ),
				$this->db->getDomainID()
			)
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
			[ 'getPropertyInfoLookup', PropertyInfoLookup::class ],
			[ 'getUsageTracker', UsageTracker::class ],
			[ 'getUsageLookup', UsageLookup::class ],
			[ 'getEntityIdLookup', EntityIdLookup::class ],
			[ 'getEntityPrefetcher', EntityPrefetcher::class ],
			[ 'getRecentChangesFinder', RecentChangesFinder::class ],
		];
	}

	public function testGetSubscriptionManager() {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( 'getSubscriptionManager needs the repository extension to be active.' );
		}

		$store = $this->newStore();

		$this->assertInstanceOf( SubscriptionManager::class, $store->getSubscriptionManager() );
	}

	/** @dataProvider provideBooleans */
	public function testGetUsageLookup( bool $enableImplicitDescriptionUsage ) {
		$this->mergeMwGlobalArrayValue( 'wgWBClientSettings', [
			'enableImplicitDescriptionUsage' => $enableImplicitDescriptionUsage,
		] );

		$store = $this->newStore();
		$usageLookup = $store->getUsageLookup();

		if ( $enableImplicitDescriptionUsage ) {
			$this->assertInstanceOf( ImplicitDescriptionUsageLookup::class, $usageLookup );
		} else {
			$this->assertNotInstanceOf( ImplicitDescriptionUsageLookup::class, $usageLookup );
		}
	}

	public function provideBooleans() {
		yield [ true ];
		yield [ false ];
	}

}
