<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\DispatchingServiceFactory;
use Wikibase\DataAccess\MultipleRepositoryAwareWikibaseServices;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\Interactors\TermSearchInteractorFactory;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\PropertyInfoLookup;

/**
 * @covers Wikibase\DataAccess\MultipleRepositoryAwareWikibaseServices
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class MultipleRepositoryAwareWikibaseServicesTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return DispatchingServiceFactory
	 */
	private function getDispatchingServiceContainer() {
		$dispatchingServiceContainer = $this->getMockBuilder( DispatchingServiceFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$dispatchingServiceContainer->method( 'getEntityInfoBuilderFactory' )
			->will(
				$this->returnValue( $this->getMock( EntityInfoBuilderFactory::class ) )
			);
		$dispatchingServiceContainer->method( 'getEntityPrefetcher' )
			->will(
				$this->returnValue( $this->getMock( EntityPrefetcher::class ) )
			);
		$dispatchingServiceContainer->method( 'getEntityRevisionLookup' )
			->will(
				$this->returnValue( $this->getMock( EntityRevisionLookup::class ) )
			);
		$dispatchingServiceContainer->method( 'getPropertyInfoLookup' )
			->will(
				$this->returnValue( $this->getMock( PropertyInfoLookup::class ) )
			);
		$dispatchingServiceContainer->method( 'getTermBuffer' )
			->will(
				$this->returnValue( $this->getMock( TermBuffer::class ) )
			);
		$dispatchingServiceContainer->method( 'getTermSearchInteractorFactory' )
			->will(
				$this->returnValue( $this->getMock( TermSearchInteractorFactory::class ) )
			);

		return $dispatchingServiceContainer;
	}

	public function testGetEntityInfoBuilderFactory() {
		$wikibaseServices = new MultipleRepositoryAwareWikibaseServices( $this->getDispatchingServiceContainer() );

		$this->assertInstanceOf( EntityInfoBuilderFactory::class, $wikibaseServices->getEntityInfoBuilderFactory() );
	}

	public function testGetEntityPrefetcher() {
		$wikibaseServices = new MultipleRepositoryAwareWikibaseServices( $this->getDispatchingServiceContainer() );

		$this->assertInstanceOf( EntityPrefetcher::class, $wikibaseServices->getEntityPrefetcher() );
	}

	public function testGetEntityRevisionLookup() {
		$wikibaseServices = new MultipleRepositoryAwareWikibaseServices( $this->getDispatchingServiceContainer() );

		$this->assertInstanceOf( EntityRevisionLookup::class, $wikibaseServices->getEntityRevisionLookup() );
	}

	public function testGetEntityStoreWatcher() {
		$wikibaseServices = new MultipleRepositoryAwareWikibaseServices( $this->getDispatchingServiceContainer() );

		$this->assertInstanceOf( EntityStoreWatcher::class, $wikibaseServices->getEntityStoreWatcher() );
	}

	public function testGetPropertyInfoLookup() {
		$wikibaseServices = new MultipleRepositoryAwareWikibaseServices( $this->getDispatchingServiceContainer() );

		$this->assertInstanceOf( PropertyInfoLookup::class, $wikibaseServices->getPropertyInfoLookup() );
	}

	public function testGetTermBuffer() {
		$wikibaseServices = new MultipleRepositoryAwareWikibaseServices( $this->getDispatchingServiceContainer() );

		$this->assertInstanceOf( TermBuffer::class, $wikibaseServices->getTermBuffer() );
	}

	public function testGetTermSearchInteractorFactory() {
		$wikibaseServices = new MultipleRepositoryAwareWikibaseServices( $this->getDispatchingServiceContainer() );

		$this->assertInstanceOf(
			TermSearchInteractorFactory::class,
			$wikibaseServices->getTermSearchInteractorFactory()
		);
	}

	public function testGetServicesIncludesServicesProvidedByDispatchingServiceContainer() {
		$dispatchingServiceContainer = $this->getMockBuilder( DispatchingServiceFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$wikibaseServices = new MultipleRepositoryAwareWikibaseServices( $dispatchingServiceContainer );

		$serviceNames = $wikibaseServices->getServiceNames();

		$this->assertContains( 'EntityInfoBuilderFactory', $serviceNames );
		$this->assertContains( 'EntityPrefetcher', $serviceNames );
		$this->assertContains( 'EntityRevisionLookup', $serviceNames );
		$this->assertContains( 'EntityStoreWatcher', $serviceNames );
		$this->assertContains( 'PropertyInfoLookup', $serviceNames );
		$this->assertContains( 'TermBuffer', $serviceNames );
		$this->assertContains( 'TermSearchInteractorFactory', $serviceNames );
	}

	public function testGetServiceReturnsSameServiceInstanceAsDispatchingServiceContainer() {
		$dispatchingServiceContainer = $this->getDispatchingServiceContainer();

		$wikibaseServices = new MultipleRepositoryAwareWikibaseServices( $dispatchingServiceContainer );

		$this->assertSame(
			$dispatchingServiceContainer->getEntityInfoBuilderFactory(),
			$wikibaseServices->getEntityInfoBuilderFactory()
		);
		$this->assertSame(
			$dispatchingServiceContainer->getEntityPrefetcher(),
			$wikibaseServices->getEntityPrefetcher()
		);
		$this->assertSame(
			$dispatchingServiceContainer->getEntityRevisionLookup(),
			$wikibaseServices->getEntityRevisionLookup()
		);
		$this->assertSame(
			$dispatchingServiceContainer->getPropertyInfoLookup(),
			$wikibaseServices->getPropertyInfoLookup()
		);
		$this->assertSame(
			$dispatchingServiceContainer->getTermBuffer(),
			$wikibaseServices->getTermBuffer()
		);
		$this->assertSame(
			$dispatchingServiceContainer->getTermSearchInteractorFactory(),
			$wikibaseServices->getTermSearchInteractorFactory()
		);
	}

}
