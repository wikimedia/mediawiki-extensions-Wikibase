<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\DataAccessSettings;

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
			false,
			DataAccessSettings::USE_REPOSITORY_PREFIX_BASED_FEDERATION,
			DataAccessSettings::PROPERTY_TERMS_UNNORMALIZED,
			DataAccessSettings::ITEM_TERMS_UNNORMALIZED_STAGE_ONLY
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
			$forceWriteSearchFields,
			DataAccessSettings::USE_REPOSITORY_PREFIX_BASED_FEDERATION,
			DataAccessSettings::PROPERTY_TERMS_UNNORMALIZED,
			DataAccessSettings::ITEM_TERMS_UNNORMALIZED_STAGE_ONLY
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

	/**
	 * @dataProvider provideBoolean
	 */
	public function testNormalizedPropertyTerms( $useNormalizedPropertyTerms ) {
		$settings = new DataAccessSettings(
			1,
			true,
			false,
			DataAccessSettings::USE_REPOSITORY_PREFIX_BASED_FEDERATION,
			$useNormalizedPropertyTerms,
			DataAccessSettings::ITEM_TERMS_UNNORMALIZED_STAGE_ONLY
		);

		$this->assertSame( $useNormalizedPropertyTerms, $settings->useNormalizedPropertyTerms() );
	}

	public function provideBoolean() {
		return [ [ false ], [ true ] ];
	}

	public function provideUseNormalizedItemTermsTest() {
		$itemTermsMigrationStages = [
			2 => MIGRATION_NEW,
			4 => MIGRATION_WRITE_NEW,
			6 => MIGRATION_WRITE_BOTH,
			'max' => MIGRATION_OLD
		];

		return [

			'item id falls in MIGRATION_NEW stage' => [
				'itemTermsMigrationStages' => $itemTermsMigrationStages,
				'numericItemId' => 1,
				'expectedReturn' => true
			],

			'item id falls in MIGRATION_WRITE_NEW stage' => [
				'itemTermsMigrationStages' => $itemTermsMigrationStages,
				'numericItemId' => 3,
				'expectedReturn' => true
			],

			'item id falls in MIGRATION_WRITE_BOTH stage' => [
				'itemTermsMigrationStages' => $itemTermsMigrationStages,
				'numericItemId' => 5,
				'expectedReturn' => false
			],

			'item id falls in MIGRATION_OLD stage' => [
				'itemTermsMigrationStages' => $itemTermsMigrationStages,
				'numericItemId' => 7,
				'expectedReturn' => false
			]
		];
	}

	/**
	 * @dataProvider provideUseNormalizedItemTermsTest
	 */
	public function testUseNormalizedItemTerms( $itemTermsMigrationStages, $numericItemId, $expectedReturn ) {
		$settings = new DataAccessSettings(
			1,
			true,
			false,
			DataAccessSettings::USE_REPOSITORY_PREFIX_BASED_FEDERATION,
			DataAccessSettings::PROPERTY_TERMS_UNNORMALIZED,
			$itemTermsMigrationStages
		);

		$this->assertSame( $expectedReturn, $settings->useNormalizedItemTerms( $numericItemId ) );
	}

}
