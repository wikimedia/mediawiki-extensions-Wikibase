<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Language;
use Wikibase\Client\Hooks\SidebarLinkBadgeDisplay;
use Wikibase\Client\Tests\Unit\ServiceWiringTest;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\TermLanguageFallbackChain;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SidebarLinkBadgeDisplayTest extends ServiceWiringTest {

	public function testConstruction() {
		$fallbackChain = $this->createMock( TermLanguageFallbackChain::class );
		$fallbackChain->method( 'getFetchLanguageCodes' )
			->willReturn( [ 'somecode' ] );

		$fallbackChainFactory = $this->createMock( LanguageFallbackChainFactory::class );
		$fallbackChainFactory->method( 'newFromLanguage' )
			->willReturn( $fallbackChain );

		$this->mockService(
			'WikibaseClient.LanguageFallbackLabelDescriptionLookupFactory',
			new LanguageFallbackLabelDescriptionLookupFactory(
				$fallbackChainFactory,
				$this->createMock( TermLookup::class ),
				$this->createMock( TermBuffer::class )
			)
		);
		$this->mockService(
			'WikibaseClient.Settings',
			new SettingsArray( [ 'badgeClassNames' => [] ] )
		);
		$this->mockService(
			'WikibaseClient.UserLanguage',
			$this->createMock( Language::class )
		);

		$this->assertInstanceOf(
			SidebarLinkBadgeDisplay::class,
			$this->getService( 'WikibaseClient.SidebarLinkBadgeDisplay' )
		);
	}
}
