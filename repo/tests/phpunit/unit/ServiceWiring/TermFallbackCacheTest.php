<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;
use Wikibase\Lib\TermFallbackCacheFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

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
		$this->mockService( 'WikibaseRepo.TermFallbackCacheFactory',
			$termFallbackCacheFactory );
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'sharedCacheDuration' => 3600,
			] ) );

		$termFallbackCache = $this->getService( 'WikibaseRepo.TermFallbackCache' );

		$this->assertInstanceOf( TermFallbackCacheFacade::class, $termFallbackCache );
	}

}
