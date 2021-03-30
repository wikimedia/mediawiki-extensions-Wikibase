<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;
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
				'sharedCacheType' => CACHE_NONE
			] )
		);

		$this->mockService(
			'WikibaseClient.PropertySource',
			new EntitySource(
				'test',
				'test',
				[],
				'',
				'',
				'',
				''
			)
		);

		$this->assertInstanceOf(
			PropertyLabelResolver::class,
			$this->getService( 'WikibaseClient.PropertyLabelResolver' )
		);
	}

}
