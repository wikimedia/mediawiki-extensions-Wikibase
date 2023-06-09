<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class MobileSiteTest extends ServiceWiringTestCase {

	public function testConstructionNoMobileFrontend(): void {
		$mobileSite = $this->getService( 'WikibaseRepo.MobileSite' );

		$this->assertFalse( $mobileSite );
	}

	public function testConstructionMobileView(): void {
		$this->mockService(
			'MobileFrontend.Context',
			new class() {
				public function shouldDisplayMobileView(): bool {
					return true;
				}
			}
		);

		$mobileSite = $this->getService( 'WikibaseRepo.MobileSite' );

		$this->assertTrue( $mobileSite );
	}

	public function testConstructionDesktopView(): void {
		$this->mockService(
			'MobileFrontend.Context',
			new class() {
				public function shouldDisplayMobileView(): bool {
					return false;
				}
			}
		);

		$mobileSite = $this->getService( 'WikibaseRepo.MobileSite' );

		$this->assertFalse( $mobileSite );
	}

}
