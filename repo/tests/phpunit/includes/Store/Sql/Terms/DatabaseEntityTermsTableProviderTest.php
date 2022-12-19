<?php

namespace Wikibase\Repo\Tests\Store\Sql\Terms;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Store\Sql\Terms\DatabaseEntityTermsTableProvider;

/**
 * @covers \Wikibase\Repo\Store\Sql\Terms\DatabaseEntityTermsTableProvider
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class DatabaseEntityTermsTableProviderTest extends TestCase {

	public function provideEntityTypeAndExpectedOutput() {
		return [
			'item' => [
				'entityType' => 'item',
				'expectedOutput' => [
					[
						'wbt_item_terms',
						'wbt_term_in_lang',
						'wbt_text_in_lang',
						'wbt_text',
					],
					// join conditions
					[
						"wbt_text" => [
							'JOIN',
							"wbxl_text_id=wbx_id",
						],
						"wbt_text_in_lang" => [
							'JOIN',
							"wbtl_text_in_lang_id=wbxl_id",
						],
						"wbt_term_in_lang" => [
							'JOIN',
							"wbit_term_in_lang_id=wbtl_id",
						],
					],
					'wbit_item_id',
				],
			],

			'property' => [
				'entityType' => 'property',
				'expectedOutput' => [
					[
						'wbt_property_terms',
						'wbt_term_in_lang',
						'wbt_text_in_lang',
						'wbt_text',
					],
					// join conditions
					[
						"wbt_text" => [
							'JOIN',
							"wbxl_text_id=wbx_id",
						],
						"wbt_text_in_lang" => [
							'JOIN',
							"wbtl_text_in_lang_id=wbxl_id",
						],
						"wbt_term_in_lang" => [
							'JOIN',
							"wbpt_term_in_lang_id=wbtl_id",
						],
					],
					'wbpt_property_id',
				],
			],
		];
	}

	/** @dataProvider provideEntityTypeAndExpectedOutput */
	public function testGetEntityTermsTableAndJoinConditions(
		$entityType,
		$expected
	) {
		$databaseEntityTermsTableProvider = new DatabaseEntityTermsTableProvider( $entityType );
		$actual = $databaseEntityTermsTableProvider->getEntityTermsTableAndJoinConditions();

		$this->assertEquals( $expected, $actual );
	}
}
