<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use MWDebug;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\NullPrefetchingTermLookup;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LanguageFallbackLabelDescriptionLookupFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseClient.LanguageFallbackChainFactory',
			$this->createMock( LanguageFallbackChainFactory::class ) );
		$prefetchingTermLookup = new NullPrefetchingTermLookup();
		$this->mockService( 'WikibaseClient.TermLookup',
			$prefetchingTermLookup );
		$this->mockService( 'WikibaseClient.TermBuffer',
			$prefetchingTermLookup );

		MWDebug::filterDeprecationForTest( '/LanguageFallbackLabelDescriptionLookupFactory/' );

		$this->assertInstanceOf(
			LanguageFallbackLabelDescriptionLookupFactory::class,
			$this->getService( 'WikibaseClient.LanguageFallbackLabelDescriptionLookupFactory' )
		);
	}

}
