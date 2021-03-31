<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\NullPrefetchingTermLookup;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LanguageFallbackLabelDescriptionLookupFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.LanguageFallbackChainFactory',
			$this->createMock( LanguageFallbackChainFactory::class ) );
		$prefetchingTermLookup = new NullPrefetchingTermLookup();
		$this->mockService( 'WikibaseRepo.TermLookup',
			$prefetchingTermLookup );
		$this->mockService( 'WikibaseRepo.TermBuffer',
			$prefetchingTermLookup );

		$this->assertInstanceOf(
			LanguageFallbackLabelDescriptionLookupFactory::class,
			$this->getService( 'WikibaseRepo.LanguageFallbackLabelDescriptionLookupFactory' )
		);
	}

}
