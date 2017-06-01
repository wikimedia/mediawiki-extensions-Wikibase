<?php

namespace Wikibase\Client\Tests\Store\Sql;

use Wikibase\Client\RecentChanges\RecentChangesDuplicateDetector;
use Wikibase\Client\Usage\SubscriptionManager;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Client\Usage\UsageTracker;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataAccess\DispatchingServiceFactory;
use Wikibase\DataAccess\RepositoryServiceContainerFactory;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Entity\NullEntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;
use Wikibase\Client\Store\Sql\DirectSqlStore;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\RepositoryDefinitions;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Tests\Store\MockPropertyInfoLookup;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\TermIndex;
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

		/** @var RepositoryServiceContainerFactory $containerFactory */
		$containerFactory = $this->getMockBuilder( RepositoryServiceContainerFactory::class )
			->disableOriginalConstructor()
			->getMock();

		/** @var RepositoryDefinitions $repositoryDefinitions */
		$repositoryDefinitions = $this->getMockBuilder( RepositoryDefinitions::class )
			->disableOriginalConstructor()
			->getMock();

		$dispatchingServiceFactory = new DispatchingServiceFactory(
			$containerFactory,
			$repositoryDefinitions
		);

		$dispatchingServiceFactory->defineService( 'EntityPrefetcher', function() {
			return new NullEntityPrefetcher();
		} );
		$dispatchingServiceFactory->defineService( 'EntityRevisionLookup', function() {
			return $this->getMock( EntityRevisionLookup::class );
		} );
		$dispatchingServiceFactory->defineService( 'PropertyInfoLookup', function() {
			return new MockPropertyInfoLookup();
		} );

		return new DirectSqlStore(
			$entityChangeFactory,
			$client->getEntityContentDataCodec(),
			new ItemIdParser(),
			new EntityIdComposer( [] ),
			new EntityNamespaceLookup( [] ),
			$dispatchingServiceFactory,
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
