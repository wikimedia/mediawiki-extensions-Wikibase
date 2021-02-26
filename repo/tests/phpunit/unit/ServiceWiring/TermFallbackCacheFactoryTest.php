<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use HashConfig;
use NullStatsdDataFactory;
use Psr\Log\NullLogger;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\TermFallbackCacheFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

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
					case 'WikibaseRepo.Settings':
						return new SettingsArray( [
							'sharedCacheType' => 'test cache type',
							'termFallbackCacheVersion' => 1,
						] );
					case 'WikibaseRepo.Logger':
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

		$termFallbackCacheFactory = $this->getService( 'WikibaseRepo.TermFallbackCacheFactory' );

		$this->assertInstanceOf( TermFallbackCacheFactory::class, $termFallbackCacheFactory );
	}

}
