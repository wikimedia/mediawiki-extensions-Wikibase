<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Language;
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

	public function testConstruction() {
		$this->mockService(
			'WikibaseClient.FallbackLabelDescriptionLookupFactory',
			$this->createMock( FallbackLabelDescriptionLookupFactory::class )
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
