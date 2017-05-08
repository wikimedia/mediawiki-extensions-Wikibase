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
use Wikibase\DirectSqlStore;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\RepositoryDefinitions;
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
