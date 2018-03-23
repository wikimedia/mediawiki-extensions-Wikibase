<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\DataAccessSettings;

/**
 * @covers Wikibase\DataAccess\DataAccessSettings
 *
 * @license GPL-2.0-or-later
 */
class DataAccessSettingsTest extends \PHPUnit\Framework\TestCase {

	public function testConvertsMaxSerializedEntitySizeFromKiloBytesToBytes() {
		$settings = new DataAccessSettings( 1, true, false );

		$this->assertEquals( 1024, $settings->maxSerializedEntitySizeInBytes() );
	}

	/**
	 * @dataProvider provideTwoBooleans
	 */
	public function testSearchFieldsGetters( $useSearchFields, $forceWriteSearchFields ) {
		$settings = new DataAccessSettings( 1, $useSearchFields, $forceWriteSearchFields );

		$this->assertSame( $useSearchFields, $settings->useSearchFields() );
		$this->assertSame( $forceWriteSearchFields, $settings->forceWriteSearchFields() );
	}

	public function provideTwoBooleans() {
		return [
			[ false, false ],
			[ false, true ],
			[ true, false ],
			[ true, true ],
		];
	}

}
