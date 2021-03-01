<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use HashConfig;
use NullStatsdDataFactory;
use Psr\Log\NullLogger;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\TermFallbackCacheFactory;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermFallbackCacheFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->serviceContainer->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->willReturnCallback( function ( string $id ) {
				switch ( $id ) {
					case 'WikibaseClient.Settings':
						return new SettingsArray( [
							'sharedCacheType' => 'test cache type',
							'termFallbackCacheVersion' => 1,
						] );
					case 'WikibaseClient.Logger':
						return new NullLogger();
					default:
						$this->fail( "Unexpected service $id" );
				}
			} );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getStatsdDataFactory' )
			->willReturn( new NullStatsdDataFactory() );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getMainConfig' )
			->willReturn( new HashConfig( [
				'SecretKey' => 'not so secret',
			] ) );

		$termFallbackCacheFactory = $this->getService( 'WikibaseClient.TermFallbackCacheFactory' );

		$this->assertInstanceOf( TermFallbackCacheFactory::class, $termFallbackCacheFactory );
	}

}
