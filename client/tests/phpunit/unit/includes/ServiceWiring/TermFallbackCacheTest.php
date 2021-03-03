<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;
use Wikibase\Lib\TermFallbackCacheFactory;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermFallbackCacheTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$termFallbackCacheFactory = $this->createMock( TermFallbackCacheFactory::class );
		$termFallbackCacheFactory->expects( $this->once() )
			->method( 'getTermFallbackCache' );
		$this->mockService( 'WikibaseClient.TermFallbackCacheFactory',
			$termFallbackCacheFactory );
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( [
				'sharedCacheDuration' => 3600,
			] ) );

		$termFallbackCache = $this->getService( 'WikibaseClient.TermFallbackCache' );

		$this->assertInstanceOf( TermFallbackCacheFacade::class, $termFallbackCache );
	}

}
