<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\DataAccessSettings;

/**
 * @covers Wikibase\DataAccess\DataAccessSettings
 *
 * @license GPL-2.0-or-later
 */
class DataAccessSettingsTest extends \PHPUnit_Framework_TestCase {

	public function testConvertsMaxSerializedEntitySizeFromKiloBytesToBytes() {
		$settings = new DataAccessSettings( 1 );

		$this->assertEquals( 1024, $settings->maxSerializedEntitySizeInBytes() );
	}

}
