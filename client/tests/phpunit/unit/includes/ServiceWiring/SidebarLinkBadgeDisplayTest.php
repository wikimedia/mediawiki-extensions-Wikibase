<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use MediaWiki\Language\Language;
use MediaWiki\StubObject\StubUserLang;
use Wikibase\Client\Hooks\SidebarLinkBadgeDisplay;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SidebarLinkBadgeDisplayTest extends ServiceWiringTestCase {

	/** @var Language|StubUserLang */
	private $cachedLang;

	protected function setUp(): void {
		parent::setUp();

		global $wgLang;

		$this->cachedLang = clone $wgLang;
	}

	protected function tearDown(): void {
		parent::tearDown();

		global $wgLang;

		$wgLang = $this->cachedLang;
	}

	public function testConstruction() {
		global $wgLang;

		$this->mockService(
			'WikibaseClient.FallbackLabelDescriptionLookupFactory',
			$this->createMock( FallbackLabelDescriptionLookupFactory::class )
		);
		$this->mockService(
			'WikibaseClient.Settings',
			new SettingsArray( [ 'badgeClassNames' => [] ] )
		);
		$wgLang = $this->createMock( Language::class );

		$this->assertInstanceOf(
			SidebarLinkBadgeDisplay::class,
			$this->getService( 'WikibaseClient.SidebarLinkBadgeDisplay' )
		);
	}
}
