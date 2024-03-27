<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\WikibaseServices;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyInfoLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$wikibaseServices = $this->createMock( WikibaseServices::class );
		$wikibaseServices->expects( $this->once() )
			->method( 'getPropertyInfoLookup' )
			->willReturn( $this->createStub( PropertyInfoLookup::class ) );
		$this->mockService( 'WikibaseRepo.WikibaseServices', $wikibaseServices );

		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'sharedCacheKeyPrefix' => 'wikibase_shared/test',
				'sharedCacheKeyGroup' => 'test',
				'sharedCacheType' => CACHE_NONE,
				'sharedCacheDuration' => 60 * 60,
			] ) );

		$this->assertInstanceOf(
			PropertyInfoLookup::class,
			$this->getService( 'WikibaseRepo.PropertyInfoLookup' )
		);
	}

}
