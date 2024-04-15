<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\PropertyInfoLookup;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyInfoLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseClient.EntityIdComposer', $this->createStub( EntityIdComposer::class ) );
		$this->mockService( 'WikibaseClient.RepoDomainDbFactory', $this->createStub( RepoDomainDbFactory::class ) );

		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( [
				'sharedCacheKeyPrefix' => 'wikibase_shared/test',
				'sharedCacheKeyGroup' => 'test',
				'sharedCacheType' => CACHE_NONE,
				'sharedCacheDuration' => 60 * 60,
			] ) );

		$this->assertInstanceOf(
			PropertyInfoLookup::class,
			$this->getService( 'WikibaseClient.PropertyInfoLookup' )
		);
	}

}
