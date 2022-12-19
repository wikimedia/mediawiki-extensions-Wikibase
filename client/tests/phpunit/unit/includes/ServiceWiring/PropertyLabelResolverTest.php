<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\SettingsArray;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyLabelResolverTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseClient.Settings',
			new SettingsArray( [
				'sharedCacheKeyPrefix' => 'test',
				'sharedCacheDuration' => 3600, // 1 Hour
				'sharedCacheType' => CACHE_NONE,
			] )
		);

		$propertySource = $this->createStub( DatabaseEntitySource::class );
		$this->mockService( 'WikibaseClient.PropertySource', $propertySource );

		$repoDb = $this->createStub( RepoDomainDb::class );
		$dbFactory = $this->createMock( RepoDomainDbFactory::class );
		$dbFactory->expects( $this->once() )
			->method( 'newForEntitySource' )
			->with( $propertySource )
			->willReturn( $repoDb );
		$this->mockService( 'WikibaseClient.RepoDomainDbFactory', $dbFactory );

		$this->assertInstanceOf(
			PropertyLabelResolver::class,
			$this->getService( 'WikibaseClient.PropertyLabelResolver' )
		);
	}

}
