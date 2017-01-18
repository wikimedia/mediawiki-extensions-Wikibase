<?php

namespace Wikibase\Client\Tests\Store;

use DataValues\Deserializers\DataValueDeserializer;
use HashSiteStore;
use Language;
use stdClass;
use Wikibase\Client\Store\RepositoryServiceContainer;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParser;
use Wikibase\EntityRevision;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\Client\Store\RepositoryServiceContainer
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0+
 */
class RepositoryServiceContainerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return WikibaseClient
	 */
	private function getWikibaseClient() {
		$settings = WikibaseClient::getDefaultInstance()->getSettings()->getArrayCopy();
		$settings['foreignRepositories'] = [
			'foo' => [ 'repoDatabase' => 'foowiki', 'prefixMapping' => [ 'bar' => 'xyz' ] ]
		];

		return new WikibaseClient(
			new SettingsArray( $settings ),
			Language::factory( 'en' ),
			new DataTypeDefinitions( [] ),
			new EntityTypeDefinitions( [] ),
			new HashSiteStore()
		);
	}

	/**
	 * @return RepositoryServiceContainer
	 */
	private function newRepositoryServiceContainer() {
		/** @var EntityIdParser $idParser */
		$idParser = $this->getMock( EntityIdParser::class );

		return new RepositoryServiceContainer(
			'foowiki',
			'foo',
			new PrefixMappingEntityIdParser( [ '' => 'foo' ], $idParser ),
			new DataValueDeserializer( [] ),
			$this->getWikibaseClient()
		);
	}

	/**
	 * @return RepositoryServiceContainer
	 */
	private function getRepositoryServiceContainer() {
		$container = $this->newRepositoryServiceContainer();

		$container->defineService( 'EntityRevisionLookup', function() {
			return $this->getMock( EntityRevisionLookup::class );
		} );

		return $container;
	}

	public function testGetService() {
		$repositoryServiceContainer = $this->getRepositoryServiceContainer();

		$serviceOne = $repositoryServiceContainer->getService( 'EntityRevisionLookup' );
		$serviceTwo = $repositoryServiceContainer->getService( 'EntityRevisionLookup' );

		$this->assertInstanceOf( EntityRevisionLookup::class, $serviceOne );
		$this->assertInstanceOf( EntityRevisionLookup::class, $serviceTwo );

		$this->assertSame( $serviceOne, $serviceTwo );
	}

	public function testGetServiceNames() {
		$repositoryServiceContainer = $this->getRepositoryServiceContainer();

		$this->assertEquals(
			[ 'EntityRevisionLookup' ],
			$repositoryServiceContainer->getServiceNames()
		);
	}

	public function testGetRepositoryName() {
		$repositoryServiceContainer = $this->getRepositoryServiceContainer();

		$this->assertEquals( 'foo', $repositoryServiceContainer->getRepositoryName() );
	}

	public function testGetDatabaseName() {
		$repositoryServiceContainer = $this->getRepositoryServiceContainer();

		$this->assertEquals( 'foowiki', $repositoryServiceContainer->getDatabaseName() );
	}

	/**
	 * @param $event
	 *
	 * @return RepositoryServiceContainer
	 */
	private function getRepositoryServiceContainerForEventTest( $event ) {
		$watcherServiceOne = $this->getMock( EntityStoreWatcher::class );
		$watcherServiceOne->expects( $this->atLeastOnce() )->method( $event );

		$watcherServiceTwo = $this->getMock( EntityStoreWatcher::class );
		$watcherServiceTwo->expects( $this->atLeastOnce() )->method( $event );

		$nonWatcherService = $this->getMock( stdClass::class );

		$container = $this->newRepositoryServiceContainer();
		$container->defineService( 'someService', function () use ( $watcherServiceOne ) {
			return $watcherServiceOne;
		} );
		$container->defineService( 'otherService', function () use ( $watcherServiceTwo ) {
			return $watcherServiceTwo;
		} );
		$container->defineService( 'anotherService', function () use ( $nonWatcherService ) {
			return $nonWatcherService;
		} );

		return $container;
	}

	public function testEntityUpdatedDelegatesEventToAllWatchers() {
		$container = $this->getRepositoryServiceContainerForEventTest( 'entityUpdated' );

		$container->entityUpdated( new EntityRevision( new Item( new ItemId( 'foo:Q123' ) ) ) );
	}

	public function testEntityDeletedDelegatesEventToAllWatchers() {
		$container = $this->getRepositoryServiceContainerForEventTest( 'entityDeleted' );

		$container->entityDeleted( new ItemId( 'foo:Q123' ) );
	}

	public function testRedirectUpdatedDelegatesEventToAllWatchers() {
		$container = $this->getRepositoryServiceContainerForEventTest( 'redirectUpdated' );

		$container->redirectUpdated( new EntityRedirect( new ItemId( 'foo:Q123' ), new ItemId( 'foo:Q321' ) ), 100 );
	}

}
