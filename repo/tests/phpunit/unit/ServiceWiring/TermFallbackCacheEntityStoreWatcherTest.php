<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;
use Wikibase\Lib\WikibaseContentLanguages;
use Wikibase\Repo\Store\TermFallbackCacheEntityStoreWatcher;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermFallbackCacheEntityStoreWatcherTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.EntityRevisionLookup', $this->createMock( EntityRevisionLookup::class ) );
		$this->mockService( 'WikibaseRepo.TermFallbackCache', $this->createMock( TermFallbackCacheFacade::class ) );
		$this->mockService( 'WikibaseRepo.WikibaseContentLanguages', $this->createMock( WikibaseContentLanguages::class ) );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getLocalServerObjectCache' );
		$this->mockService( 'WikibaseRepo.LanguageFallbackChainFactory', $this->createMock( LanguageFallbackChainFactory::class ) );
		$termFallbackCacheEntityStoreWatcher = $this->getService( 'WikibaseRepo.TermFallbackCacheEntityStoreWatcher' );

		$this->assertInstanceOf( TermFallbackCacheEntityStoreWatcher::class, $termFallbackCacheEntityStoreWatcher );
	}

}
