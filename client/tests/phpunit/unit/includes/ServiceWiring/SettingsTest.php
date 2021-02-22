<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\SettingsArray;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SettingsTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$settings = $this->getService( 'WikibaseClient.Settings' );

		$this->assertInstanceOf( SettingsArray::class, $settings );
	}

}
