<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SettingsTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$settings = $this->getService( 'WikibaseRepo.Settings' );

		$this->assertInstanceOf( SettingsArray::class, $settings );
	}

}
