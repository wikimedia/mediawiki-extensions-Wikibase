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

}
