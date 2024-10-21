<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use ObjectCacheFactory;
use Wikibase\Repo\Store\BagOStuffSiteLinkConflictLookup;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;
use Wikimedia\ObjectCache\BagOStuff;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class BagOStuffSiteLinkConflictLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$objectCacheFactory = $this->createMock( ObjectCacheFactory::class );
		$objectCacheFactory->expects( $this->once() )
			->method( 'getLocalClusterInstance' )
			->willReturn( $this->createMock( BagOStuff::class ) );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getObjectCacheFactory' )
			->willReturn( $objectCacheFactory );

		$this->assertInstanceOf(
			BagOStuffSiteLinkConflictLookup::class,
			$this->getService( 'WikibaseRepo.BagOStuffSiteLinkConflictLookup' )
		);
	}

}
