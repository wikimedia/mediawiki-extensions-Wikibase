<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataModel\Entity\Int32EntityId;

/**
 * @covers \Wikibase\DataAccess\DataAccessSettings
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DataAccessSettingsTest extends \PHPUnit\Framework\TestCase {

	public function testConvertsMaxSerializedEntitySizeFromKiloBytesToBytes() {
		$settings = new DataAccessSettings(
			1,
			true,
			false
		);

		$this->assertEquals( 1024, $settings->maxSerializedEntitySizeInBytes() );
	}

	/**
	 * @dataProvider provideTwoBooleans
	 */
	public function testSearchFieldsGetters( $useSearchFields, $forceWriteSearchFields ) {
		$settings = new DataAccessSettings(
			1,
			$useSearchFields,
			$forceWriteSearchFields
		);

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

	public function testNormalizedPropertyTerms() {
		$settings = new DataAccessSettings(
			1,
			true,
			false
		);

		$this->assertTrue( $settings->useNormalizedPropertyTerms() );
	}

	public function testUseNormalizedItemTerms() {
		$settings = new DataAccessSettings(
			1,
			true,
			false
		);

		$this->assertTrue( $settings->useNormalizedItemTerms( 1 ) );
		$this->assertTrue( $settings->useNormalizedItemTerms( Int32EntityId::MAX ) );
	}

}
