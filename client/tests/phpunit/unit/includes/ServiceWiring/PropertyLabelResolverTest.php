<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;
use Wikibase\Lib\Rdbms\TermsDomainDb;
use Wikibase\Lib\Rdbms\TermsDomainDbFactory;
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

		$termsDb = $this->createStub( TermsDomainDb::class );
		$dbFactory = $this->createMock( TermsDomainDbFactory::class );
		$dbFactory->expects( $this->once() )
			->method( 'newForEntitySource' )
			->with( $propertySource )
			->willReturn( $termsDb );
		$this->mockService( 'WikibaseClient.TermsDomainDbFactory', $dbFactory );

		$this->serviceContainer->expects( $this->once() )
			->method( 'getObjectCacheFactory' );

		$this->assertInstanceOf(
			PropertyLabelResolver::class,
			$this->getService( 'WikibaseClient.PropertyLabelResolver' )
		);
	}

}
