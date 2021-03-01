<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use InvalidArgumentException;
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
		$this->serviceContainer->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->willReturnCallback( function ( string $id ) {
				switch ( $id ) {
					case 'WikibaseClient.TermFallbackCacheFactory':
						$termFallbackCacheFactory = $this->createMock( TermFallbackCacheFactory::class );
						$termFallbackCacheFactory->expects( $this->once() )
							->method( 'getTermFallbackCache' );
						return $termFallbackCacheFactory;
					case 'WikibaseClient.Settings':
						return new SettingsArray( [
							'sharedCacheDuration' => 3600,
						] );
					default:
						throw new InvalidArgumentException( "Unknown service $id" );
				}
			} );

		$termFallbackCache = $this->getService( 'WikibaseClient.TermFallbackCache' );

		$this->assertInstanceOf( TermFallbackCacheFacade::class, $termFallbackCache );
	}

}
