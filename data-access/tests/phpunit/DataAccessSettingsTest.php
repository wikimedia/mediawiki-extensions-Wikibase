<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\DataAccessSettings;

/**
 * @covers Wikibase\DataAccess\DataAccessSettings
 *
 * @license GPL-2.0+
 */
class DataAccessSettingsTest extends \PHPUnit_Framework_TestCase {

	public function testConvertsMaxSerializedEntitySizeFromKiloBytesToBytes() {
		$settings = new DataAccessSettings( 1, true );

		$this->assertEquals( 1024, $settings->maxSerializedEntitySizeInBytes() );
	}

	public function testReturnsReadFullEntityIdColumn() {
		$size = 0;
		$this->assertEquals( false, ( new DataAccessSettings( $size, false ) )->readFullEntityIdColumn() );
		$this->assertEquals( true, ( new DataAccessSettings( $size, true ) )->readFullEntityIdColumn() );
	}

}
