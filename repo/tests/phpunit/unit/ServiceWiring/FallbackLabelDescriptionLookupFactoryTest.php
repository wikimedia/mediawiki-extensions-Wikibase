<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\NullPrefetchingTermLookup;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FallbackLabelDescriptionLookupFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.LanguageFallbackChainFactory',
			$this->createMock( LanguageFallbackChainFactory::class ) );
		$this->mockService( 'WikibaseRepo.RedirectResolvingLatestRevisionLookup',
			$this->createMock( RedirectResolvingLatestRevisionLookup::class ) );
		$this->mockService( 'WikibaseRepo.TermFallbackCache',
			$this->createMock( TermFallbackCacheFacade::class ) );
		$nullPrefetchingTermLookup = new NullPrefetchingTermLookup();
		$this->mockService( 'WikibaseRepo.TermLookup',
			$nullPrefetchingTermLookup );
		$this->mockService( 'WikibaseRepo.TermBuffer',
			$nullPrefetchingTermLookup );

		$this->assertInstanceOf( FallbackLabelDescriptionLookupFactory::class,
			$this->getService( 'WikibaseRepo.FallbackLabelDescriptionLookupFactory' ) );
	}

}
