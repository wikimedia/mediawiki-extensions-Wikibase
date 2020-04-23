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
				'alias' => 'foo',
				'entityType' => 'item',
				'expectedOutput' => [
					[
						"fooEntityTermsJoin" => [
							"foo" => 'wbt_item_terms',
							"fooTermInLangJoin" => [
								"fooTermInLang" => 'wbt_term_in_lang',
								"fooTextInLangJoin" => [
									"fooTextInLang" => 'wbt_text_in_lang',
									"fooTextJoin" => [ "fooText" => 'wbt_text' ]
								]
							]
						]
					],
					// join conditions
					[
						"fooTextJoin" => [
							'JOIN',
							"fooTextInLang.wbxl_text_id=fooText.wbx_id"
						],
						"fooTextInLangJoin" => [
							'JOIN',
							"fooTermInLang.wbtl_text_in_lang_id=fooTextInLang.wbxl_id"
						],
						"fooTermInLangJoin" => [
							'JOIN',
							"foo.wbit_term_in_lang_id=fooTermInLang.wbtl_id"
						]
					],
					'wbit_item_id'
				],
			],

			'property' => [
				'alias' => 'foo',
				'entityType' => 'property',
				'expectedOutput' => [
					[
						"fooEntityTermsJoin" => [
							"foo" => 'wbt_property_terms',
							"fooTermInLangJoin" => [
								"fooTermInLang" => 'wbt_term_in_lang',
								"fooTextInLangJoin" => [
									"fooTextInLang" => 'wbt_text_in_lang',
									"fooTextJoin" => [ "fooText" => 'wbt_text' ]
								]
							]
						]
					],
					// join conditions
					[
						"fooTextJoin" => [
							'JOIN',
							"fooTextInLang.wbxl_text_id=fooText.wbx_id"
						],
						"fooTextInLangJoin" => [
							'JOIN',
							"fooTermInLang.wbtl_text_in_lang_id=fooTextInLang.wbxl_id"
						],
						"fooTermInLangJoin" => [
							'JOIN',
							"foo.wbpt_term_in_lang_id=fooTermInLang.wbtl_id"
						]
					],
					'wbpt_property_id'
				],
			]
		];
	}

	/** @dataProvider provideEntityTypeAndExpectedOutput */
	public function testGetEntityTermsTableAndJoinConditions(
		$alias,
		$entityType,
		$expected
	) {
		$databaseEntityTermsTableProvider = new DatabaseEntityTermsTableProvider( $entityType );
		$actual = $databaseEntityTermsTableProvider->getEntityTermsTableAndJoinConditions( $alias );

		$this->assertEquals( $expected, $actual );
	}
}
