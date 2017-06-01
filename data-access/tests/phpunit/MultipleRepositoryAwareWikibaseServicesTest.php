<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\MultiRepositoryServices;
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
	 * @return MultiRepositoryServices
	 */
	private function getMultiRepositoryServices() {
		$multiRepositoryServices = $this->getMockBuilder( MultiRepositoryServices::class )
			->disableOriginalConstructor()
			->getMock();

		$multiRepositoryServices->method( 'getEntityInfoBuilderFactory' )
			->will(
				$this->returnValue( $this->getMock( EntityInfoBuilderFactory::class ) )
			);
		$multiRepositoryServices->method( 'getEntityPrefetcher' )
			->will(
				$this->returnValue( $this->getMock( EntityPrefetcher::class ) )
			);
		$multiRepositoryServices->method( 'getEntityRevisionLookup' )
			->will(
				$this->returnValue( $this->getMock( EntityRevisionLookup::class ) )
			);
		$multiRepositoryServices->method( 'getPropertyInfoLookup' )
			->will(
				$this->returnValue( $this->getMock( PropertyInfoLookup::class ) )
			);
		$multiRepositoryServices->method( 'getTermBuffer' )
			->will(
				$this->returnValue( $this->getMock( TermBuffer::class ) )
			);
		$multiRepositoryServices->method( 'getTermSearchInteractorFactory' )
			->will(
				$this->returnValue( $this->getMock( TermSearchInteractorFactory::class ) )
			);

		return $multiRepositoryServices;
	}

	public function testGetEntityInfoBuilderFactory() {
		$wikibaseServices = new MultipleRepositoryAwareWikibaseServices( $this->getMultiRepositoryServices() );

		$this->assertInstanceOf( EntityInfoBuilderFactory::class, $wikibaseServices->getEntityInfoBuilderFactory() );
	}

	public function testGetEntityPrefetcher() {
		$wikibaseServices = new MultipleRepositoryAwareWikibaseServices( $this->getMultiRepositoryServices() );

		$this->assertInstanceOf( EntityPrefetcher::class, $wikibaseServices->getEntityPrefetcher() );
	}

	public function testGetEntityRevisionLookup() {
		$wikibaseServices = new MultipleRepositoryAwareWikibaseServices( $this->getMultiRepositoryServices() );

		$this->assertInstanceOf( EntityRevisionLookup::class, $wikibaseServices->getEntityRevisionLookup() );
	}

	public function testGetEntityStoreWatcher() {
		$wikibaseServices = new MultipleRepositoryAwareWikibaseServices( $this->getMultiRepositoryServices() );

		$this->assertInstanceOf( EntityStoreWatcher::class, $wikibaseServices->getEntityStoreWatcher() );
	}

	public function testGetPropertyInfoLookup() {
		$wikibaseServices = new MultipleRepositoryAwareWikibaseServices( $this->getMultiRepositoryServices() );

		$this->assertInstanceOf( PropertyInfoLookup::class, $wikibaseServices->getPropertyInfoLookup() );
	}

	public function testGetTermBuffer() {
		$wikibaseServices = new MultipleRepositoryAwareWikibaseServices( $this->getMultiRepositoryServices() );

		$this->assertInstanceOf( TermBuffer::class, $wikibaseServices->getTermBuffer() );
	}

	public function testGetTermSearchInteractorFactory() {
		$wikibaseServices = new MultipleRepositoryAwareWikibaseServices( $this->getMultiRepositoryServices() );

		$this->assertInstanceOf(
			TermSearchInteractorFactory::class,
			$wikibaseServices->getTermSearchInteractorFactory()
		);
	}

	public function testGetServicesIncludesServicesProvidedByDispatchingServiceContainer() {
		$multiRepositoryServices = $this->getMockBuilder( MultiRepositoryServices::class )
			->disableOriginalConstructor()
			->getMock();

		$wikibaseServices = new MultipleRepositoryAwareWikibaseServices( $multiRepositoryServices );

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
		$multiRepositoryServices = $this->getMultiRepositoryServices();

		$wikibaseServices = new MultipleRepositoryAwareWikibaseServices( $multiRepositoryServices );

		$this->assertSame(
			$multiRepositoryServices->getEntityInfoBuilderFactory(),
			$wikibaseServices->getEntityInfoBuilderFactory()
		);
		$this->assertSame(
			$multiRepositoryServices->getEntityPrefetcher(),
			$wikibaseServices->getEntityPrefetcher()
		);
		$this->assertSame(
			$multiRepositoryServices->getEntityRevisionLookup(),
			$wikibaseServices->getEntityRevisionLookup()
		);
		$this->assertSame(
			$multiRepositoryServices->getPropertyInfoLookup(),
			$wikibaseServices->getPropertyInfoLookup()
		);
		$this->assertSame(
			$multiRepositoryServices->getTermBuffer(),
			$wikibaseServices->getTermBuffer()
		);
		$this->assertSame(
			$multiRepositoryServices->getTermSearchInteractorFactory(),
			$wikibaseServices->getTermSearchInteractorFactory()
		);
	}

}
