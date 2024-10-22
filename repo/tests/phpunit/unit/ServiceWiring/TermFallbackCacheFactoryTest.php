<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use MediaWiki\Config\HashConfig;
use Psr\Log\NullLogger;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\TermFallbackCacheFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;
use Wikimedia\Stats\NullStatsdDataFactory;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermFallbackCacheFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'sharedCacheType' => 'test cache type',
				'termFallbackCacheVersion' => 1,
			] ) );
		$this->mockService( 'WikibaseRepo.Logger',
			new NullLogger() );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getStatsdDataFactory' )
			->willReturn( new NullStatsdDataFactory() );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getMainConfig' )
			->willReturn( new HashConfig( [
				'SecretKey' => 'not so secret',
			] ) );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getObjectCacheFactory' );

		$termFallbackCacheFactory = $this->getService( 'WikibaseRepo.TermFallbackCacheFactory' );

		$this->assertInstanceOf( TermFallbackCacheFactory::class, $termFallbackCacheFactory );
	}

}
