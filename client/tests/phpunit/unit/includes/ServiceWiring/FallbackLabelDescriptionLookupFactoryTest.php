<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\NullPrefetchingTermLookup;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FallbackLabelDescriptionLookupFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseClient.LanguageFallbackChainFactory',
			$this->createMock( LanguageFallbackChainFactory::class ) );
		$this->mockService( 'WikibaseClient.RedirectResolvingLatestRevisionLookup',
			$this->createMock( RedirectResolvingLatestRevisionLookup::class ) );
		$this->mockService( 'WikibaseClient.TermFallbackCache',
			$this->createMock( TermFallbackCacheFacade::class ) );
		$nullPrefetchingTermLookup = new NullPrefetchingTermLookup();
		$this->mockService( 'WikibaseClient.TermLookup',
			$nullPrefetchingTermLookup );
		$this->mockService( 'WikibaseClient.TermBuffer',
			$nullPrefetchingTermLookup );

		$this->assertInstanceOf( FallbackLabelDescriptionLookupFactory::class,
			$this->getService( 'WikibaseClient.FallbackLabelDescriptionLookupFactory' ) );
	}

}
