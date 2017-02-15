<?php

namespace Wikibase\Client\Tests;

use Wikibase\Client\DispatchingServiceFactory;
use Wikibase\Client\Store\RepositoryServiceContainer;
use Wikibase\Client\Store\RepositoryServiceContainerFactory;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;

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
	 * @return RepositoryServiceContainerFactory
	 */
	private function getRepositoryServiceContainerFactory() {
		$entityRevisionLookup = $this->getMock( EntityRevisionLookup::class );

		$container = $this->getMockBuilder( RepositoryServiceContainer::class )
			->disableOriginalConstructor()
			->getMock();
		$container->expects( $this->any() )
			->method( 'getService' )
			->will(
				$this->returnCallback( function ( $service ) use ( $entityRevisionLookup ) {
					return $service === 'EntityRevisionLookup' ? $entityRevisionLookup : null;
				} )
			);

		$containerFactory = $this->getMockBuilder( RepositoryServiceContainerFactory::class )
			->disableOriginalConstructor()
			->getMock();
		$containerFactory->expects( $this->any() )
			->method( 'newContainer' )
			->will( $this->returnValue( $container ) );

		return $containerFactory;
	}

	/**
	 * @param RepositoryServiceContainerFactory $containerFactory
	 *
	 * @return DispatchingServiceFactory
	 */
	private function getDispatchingServiceFactory( RepositoryServiceContainerFactory $containerFactory ) {
		$factory = new DispatchingServiceFactory(
			$containerFactory,
			[ '', 'foo' ],
			[ 'item' => '', 'property' => 'foo' ]
		);

		$factory->defineService( 'EntityRevisionLookup', function() {
			return $this->getMock( EntityRevisionLookup::class );
		} );

		return $factory;
	}

	public function testGetEntityTypeToRepoMapping() {
		$factory = $this->getDispatchingServiceFactory( $this->getRepositoryServiceContainerFactory() );

		$this->assertEquals(
			[
				'item' => '',
				'property' => 'foo',
			],
			$factory->getEntityTypeToRepoMapping()
		);
	}

	public function testGetServiceNames() {
		$factory = $this->getDispatchingServiceFactory( $this->getRepositoryServiceContainerFactory() );

		$this->assertEquals(
			[ 'EntityRevisionLookup' ],
			$factory->getServiceNames()
		);
	}

	public function testGetServiceMap() {
		$factory = $this->getDispatchingServiceFactory( $this->getRepositoryServiceContainerFactory() );

		$serviceMap = $factory->getServiceMap( 'EntityRevisionLookup' );

		$this->assertEquals(
			[ '', 'foo' ],
			array_keys( $serviceMap )
		);
		$this->assertContainsOnlyInstancesOf( EntityRevisionLookup::class, $serviceMap );
	}

	public function testGetService() {
		$factory = $this->getDispatchingServiceFactory( $this->getRepositoryServiceContainerFactory() );

		$serviceOne = $factory->getService( 'EntityRevisionLookup' );
		$serviceTwo = $factory->getService( 'EntityRevisionLookup' );

		$this->assertInstanceOf( EntityRevisionLookup::class, $serviceOne );
		$this->assertInstanceOf( EntityRevisionLookup::class, $serviceTwo );
		$this->assertSame( $serviceOne, $serviceTwo );
	}

	/**
	 * @param string $event
	 *
	 * @return RepositoryServiceContainerFactory
	 */
	private function getRepositoryServiceContainerFactoryForEventTest( $event ) {
		$localServiceContainer = $this->getMockBuilder( RepositoryServiceContainer::class )
			->disableOriginalConstructor()
			->getMock();
		$localServiceContainer->expects( $this->never() )->method( $event );

		$fooServiceContainer = $this->getMockBuilder( RepositoryServiceContainer::class )
			->disableOriginalConstructor()
			->getMock();
		$fooServiceContainer->expects( $this->atLeastOnce() )->method( $event );

		$containerFactory = $this->getMockBuilder( RepositoryServiceContainerFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$containerFactory->expects( $this->any() )
			->method( 'newContainer' )
			->will(
				$this->returnCallback( function ( $container ) use ( $localServiceContainer, $fooServiceContainer ) {
					return $container === '' ? $localServiceContainer : $fooServiceContainer;
				} )
			);

		return $containerFactory;
	}

	public function testEntityUpdatedDelegatesEventToContainerOfRelevantRepository() {
		$factory = $this->getDispatchingServiceFactory(
			$this->getRepositoryServiceContainerFactoryForEventTest( 'entityUpdated' )
		);

		$factory->entityUpdated( new EntityRevision( new Item( new ItemId( 'foo:Q123' ) ) ) );
	}

	public function testEntityDeletedDelegatesEventToContainerOfRelevantRepository() {
		$factory = $this->getDispatchingServiceFactory(
			$this->getRepositoryServiceContainerFactoryForEventTest( 'entityDeleted' )
		);

		$factory->entityDeleted( new ItemId( 'foo:Q123' ) );
	}

	public function testRedirectUpdatedDelegatesEventToContainerOfRelevantRepository() {
		$factory = $this->getDispatchingServiceFactory(
			$this->getRepositoryServiceContainerFactoryForEventTest( 'redirectUpdated' )
		);

		$factory->redirectUpdated( new EntityRedirect( new ItemId( 'foo:Q123' ), new ItemId( 'foo:Q321' ) ), 100 );
	}

}
