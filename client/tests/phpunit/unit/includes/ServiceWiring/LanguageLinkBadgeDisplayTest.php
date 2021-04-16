<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\Hooks\SidebarLinkBadgeDisplay;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LanguageLinkBadgeDisplayTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseClient.SidebarLinkBadgeDisplay',
			$this->createMock( SidebarLinkBadgeDisplay::class ) );

		$this->assertInstanceOf(
			LanguageLinkBadgeDisplay::class,
			$this->getService( 'WikibaseClient.LanguageLinkBadgeDisplay' )
		);
	}

}
