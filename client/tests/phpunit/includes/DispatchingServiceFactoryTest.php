<?php

namespace Wikibase\Client\Tests;

use DataValues\Deserializers\DataValueDeserializer;
use Wikibase\Client\DispatchingServiceFactory;
use Wikibase\Client\Store\RepositoryServiceContainer;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;

/**
 * @covers Wikibase\Client\DispatchingServiceFactory
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0+
 */
class DispatchingServiceFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return DispatchingServiceFactory
	 */
	private function getDispatchingServiceFactory() {
		$client = WikibaseClient::getDefaultInstance();
		$settings = $client->getSettings();
		$settings->setSetting( 'foreignRepositories', [ 'foo' => [ 'repoDatabase' => 'foowiki' ] ] );

		$factory = new DispatchingServiceFactory( $client );

		$factory->defineService( 'EntityRevisionLookup', function() {
			return $this->getMock( EntityRevisionLookup::class );
		} );

		return $factory;
	}

	public function testGetServiceNames() {
		$factory = $this->getDispatchingServiceFactory();

		$this->assertEquals(
			[ 'EntityRevisionLookup' ],
			$factory->getServiceNames()
		);
	}

	public function testGetServiceMap() {
		$factory = $this->getDispatchingServiceFactory();

		$serviceMap = $factory->getServiceMap( 'EntityRevisionLookup' );

		$this->assertEquals(
			[ '', 'foo' ],
			array_keys( $serviceMap )
		);
		$this->assertContainsOnlyInstancesOf( EntityRevisionLookup::class, $serviceMap );
	}

	public function testGetService() {
		$factory = $this->getDispatchingServiceFactory();

		$serviceOne = $factory->getService( 'EntityRevisionLookup' );
		$serviceTwo = $factory->getService( 'EntityRevisionLookup' );

		$this->assertInstanceOf( EntityRevisionLookup::class, $serviceOne );
		$this->assertInstanceOf( EntityRevisionLookup::class, $serviceTwo );
		$this->assertSame( $serviceOne, $serviceTwo );
	}

	/**
	 * @param string|false $dbName
	 * @param string $repositoryName
	 *
	 * @return RepositoryServiceContainer
	 */
	private function getRepositoryServiceContainer( $dbName, $repositoryName ) {
		return new RepositoryServiceContainer(
			$dbName,
			$repositoryName,
			new BasicEntityIdParser(),
			new DataValueDeserializer( [] ),
			WikibaseClient::getDefaultInstance()
		);
	}

	public function testEntityUpdatedDelegatesEventToRepositorySpecificWatcher() {
		$localMetaDataAccessor = $this->getMockBuilder( PrefetchingWikiPageEntityMetaDataAccessor::class )
			->disableOriginalConstructor()
			->getMock();
		$localMetaDataAccessor->expects( $this->never() )->method( 'entityUpdated' );

		$fooMetaDataAccessor = $this->getMockBuilder( PrefetchingWikiPageEntityMetaDataAccessor::class )
			->disableOriginalConstructor()
			->getMock();
		$fooMetaDataAccessor->expects( $this->atLeastOnce() )->method( 'entityUpdated' );

		$localServiceContainer = $this->getRepositoryServiceContainer( false, '' );
		$localServiceContainer->defineService( 'EntityPrefetcher', function () use ( $localMetaDataAccessor ) {
			return $localMetaDataAccessor;
		} );

		$fooServiceContainer = $this->getRepositoryServiceContainer( 'foo', 'foowiki' );
		$fooServiceContainer->defineService( 'EntityPrefetcher', function () use ( $fooMetaDataAccessor ) {
			return $fooMetaDataAccessor;
		} );

		$factory = $this->getDispatchingServiceFactory();
		$factory->overrideRepositoryServiceContainers( [
			'' => $localServiceContainer,
			'foo' => $fooServiceContainer,
		] );

		$factory->entityUpdated( new EntityRevision( new Item( new ItemId( 'foo:Q123' ) ) ) );
	}

	public function testEntityDeletedDelegatesEventToRepositorySpecificWatcher() {
		$localMetaDataAccessor = $this->getMockBuilder( PrefetchingWikiPageEntityMetaDataAccessor::class )
			->disableOriginalConstructor()
			->getMock();
		$localMetaDataAccessor->expects( $this->never() )->method( 'entityDeleted' );

		$fooMetaDataAccessor = $this->getMockBuilder( PrefetchingWikiPageEntityMetaDataAccessor::class )
			->disableOriginalConstructor()
			->getMock();
		$fooMetaDataAccessor->expects( $this->atLeastOnce() )->method( 'entityDeleted' );

		$localServiceContainer = $this->getRepositoryServiceContainer( false, '' );
		$localServiceContainer->defineService( 'EntityPrefetcher', function () use ( $localMetaDataAccessor ) {
			return $localMetaDataAccessor;
		} );

		$fooServiceContainer = $this->getRepositoryServiceContainer( 'foo', 'foowiki' );
		$fooServiceContainer->defineService( 'EntityPrefetcher', function () use ( $fooMetaDataAccessor ) {
			return $fooMetaDataAccessor;
		} );

		$factory = $this->getDispatchingServiceFactory();
		$factory->overrideRepositoryServiceContainers( [
			'' => $localServiceContainer,
			'foo' => $fooServiceContainer,
		] );

		$factory->entityDeleted( new ItemId( 'foo:Q123' ) );
	}

	public function testRedirectUpdatedDelegatesEventToRepositorySpecificWatcher() {
		$localMetaDataAccessor = $this->getMockBuilder( PrefetchingWikiPageEntityMetaDataAccessor::class )
			->disableOriginalConstructor()
			->getMock();
		$localMetaDataAccessor->expects( $this->never() )->method( 'redirectUpdated' );

		$fooMetaDataAccessor = $this->getMockBuilder( PrefetchingWikiPageEntityMetaDataAccessor::class )
			->disableOriginalConstructor()
			->getMock();
		$fooMetaDataAccessor->expects( $this->atLeastOnce() )->method( 'redirectUpdated' );

		$localServiceContainer = $this->getRepositoryServiceContainer( false, '' );
		$localServiceContainer->defineService( 'EntityPrefetcher', function () use ( $localMetaDataAccessor ) {
			return $localMetaDataAccessor;
		} );

		$fooServiceContainer = $this->getRepositoryServiceContainer( 'foo', 'foowiki' );
		$fooServiceContainer->defineService( 'EntityPrefetcher', function () use ( $fooMetaDataAccessor ) {
			return $fooMetaDataAccessor;
		} );

		$factory = $this->getDispatchingServiceFactory();
		$factory->overrideRepositoryServiceContainers( [
			'' => $localServiceContainer,
			'foo' => $fooServiceContainer,
		] );

		$factory->redirectUpdated( new EntityRedirect( new ItemId( 'foo:Q123' ), new ItemId( 'foo:Q321' ) ), 100 );
	}

}
