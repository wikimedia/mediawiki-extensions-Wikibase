<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use MediaWikiIntegrationTestCase;
use Wikibase\Lib\Store\Sql\Terms\DatabaseInnerTermStoreCleaner;
use Wikibase\Lib\WikibaseSettings;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\DatabaseInnerTermStoreCleaner
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class DatabaseInnerTermStoreCleanerTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have local term store tables." );
		}

		parent::setUp();
		$this->tablesUsed[] = 'wbt_type';
		$this->tablesUsed[] = 'wbt_text';
		$this->tablesUsed[] = 'wbt_text_in_lang';
		$this->tablesUsed[] = 'wbt_term_in_lang';

		// Make sure the tables we are inserting to are empty
		$this->db->truncate(
			[
				'wbt_type',
				'wbt_text',
				'wbt_text_in_lang',
				'wbt_term_in_lang',
			]
		);
	}

	private function getCleaner(): DatabaseInnerTermStoreCleaner {
		return new DatabaseInnerTermStoreCleaner();
	}

	public function testCleanupEverything() {
		$this->db->insert( 'wbt_type',
			[ 'wby_name' => 'label' ] );
		$typeId = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'a label' ] );
		$text1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'eine Bezeichnung' ] );
		$text2Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'en', 'wbxl_text_id' => $text1Id ] );
		$textInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'de', 'wbxl_text_id' => $text2Id ] );
		$textInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $typeId, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $typeId, 'wbtl_text_in_lang_id' => $textInLang2Id ] );
		$termInLang2Id = $this->db->insertId();

		$this->getCleaner()->cleanTermInLangIds( $this->db, $this->db, [ $termInLang1Id, $termInLang2Id ] );

		$this->assertSelect( 'wbt_text', 'wbx_id', '*', [] );
		$this->assertSelect( 'wbt_text_in_lang', 'wbxl_id', '*', [] );
		$this->assertSelect( 'wbt_term_in_lang', 'wbtl_id', '*', [] );
		$this->assertSelect( 'wbt_type', 'wby_name', '*', [ [ 'label' ] ] );
	}

	public function testCleanupTermInLangButNoTextInLang() {
		$this->db->insert( 'wbt_type',
			[ 'wby_name' => 'label' ] );
		$type1Id = $this->db->insertId();
		$this->db->insert( 'wbt_type',
			[ 'wby_name' => 'description' ] );
		$type2Id = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'some text' ] );
		$text1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'etwas Text' ] );
		$text2Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'en', 'wbxl_text_id' => $text1Id ] );
		$textInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'de', 'wbxl_text_id' => $text2Id ] );
		$textInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $type1Id, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $type2Id, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $type1Id, 'wbtl_text_in_lang_id' => $textInLang2Id ] );
		$termInLang3Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $type2Id, 'wbtl_text_in_lang_id' => $textInLang2Id ] );
		$termInLang4Id = $this->db->insertId();

		$this->getCleaner()->cleanTermInLangIds( $this->db, $this->db, [ $termInLang1Id, $termInLang4Id ] );

		$this->assertSelect(
			'wbt_text',
			'wbx_id',
			'*',
			[ [ $text1Id ], [ $text2Id ] ],
			[ 'ORDER BY' => 'wbx_id' ]
		);
		$this->assertSelect(
			'wbt_text_in_lang',
			'wbxl_id',
			'*',
			[ [ $textInLang1Id ], [ $textInLang2Id ] ],
			[ 'ORDER BY' => 'wbxl_id' ]
		);
		$this->assertSelect(
			'wbt_term_in_lang',
			'wbtl_id',
			'*',
			[ [ $termInLang2Id ], [ $termInLang3Id ] ],
			[ 'ORDER BY' => 'wbtl_id' ]
		);
	}

	public function testCleanupOneTextInLangButNoText() {
		$this->db->insert( 'wbt_type',
			[ 'wby_name' => 'label' ] );
		$typeId = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'text' ] );
		$text1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'Text' ] );
		$text2Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'en', 'wbxl_text_id' => $text1Id ] );
		$textInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'de', 'wbxl_text_id' => $text2Id ] );
		$textInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'fr', 'wbxl_text_id' => $text1Id ] );
		$textInLang3Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $typeId, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $typeId, 'wbtl_text_in_lang_id' => $textInLang2Id ] );
		$termInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $typeId, 'wbtl_text_in_lang_id' => $textInLang3Id ] );
		$termInLang3Id = $this->db->insertId();

		$this->getCleaner()->cleanTermInLangIds( $this->db, $this->db, [ $termInLang1Id ] );

		// $textInLang1Id and $termInLang1Id gone,
		// but $text1Id still there because referenced by $termInLang3Id
		$this->assertSelect(
			'wbt_text',
			'wbx_id',
			'*',
			[ [ $text1Id ], [ $text2Id ] ],
			[ 'ORDER BY' => 'wbx_id' ]
		);
		$this->assertSelect(
			'wbt_text_in_lang',
			'wbxl_id',
			'*',
			[ [ $textInLang2Id ], [ $textInLang3Id ] ],
			[ 'ORDER BY' => 'wbxl_id' ]
		);
		$this->assertSelect(
			'wbt_term_in_lang',
			'wbtl_id',
			'*',
			[ [ $termInLang2Id ], [ $termInLang3Id ] ],
			[ 'ORDER BY' => 'wbtl_id' ]
		);
	}

	public function testCleanupOneText() {
		$this->db->insert( 'wbt_type',
			[ 'wby_name' => 'label' ] );
		$typeId = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'text' ] );
		$text1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'Text' ] );
		$text2Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'en', 'wbxl_text_id' => $text1Id ] );
		$textInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'de', 'wbxl_text_id' => $text2Id ] );
		$textInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $typeId, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $typeId, 'wbtl_text_in_lang_id' => $textInLang2Id ] );
		$termInLang2Id = $this->db->insertId();

		$this->getCleaner()->cleanTermInLangIds( $this->db, $this->db, [ $termInLang1Id ] );

		// $textId1, $textInLang1Id and $termInLang1Id gone
		$this->assertSelect(
			'wbt_text',
			'wbx_id',
			'*',
			[ [ $text2Id ] ]
		);
		$this->assertSelect(
			'wbt_text_in_lang',
			'wbxl_id',
			'*',
			[ [ $textInLang2Id ] ]
		);
		$this->assertSelect(
			'wbt_term_in_lang',
			'wbtl_id',
			'*',
			[ [ $termInLang2Id ] ]
		);
	}

	public function testCleanupLeavesUnrelatedTextsUntouched() {
		$this->db->insert( 'wbt_type',
			[ 'wby_name' => 'label' ] );
		$typeId = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'a label' ] );
		$text1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'eine Bezeichnung' ] );
		$text2Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'en', 'wbxl_text_id' => $text1Id ] );
		$textInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'de', 'wbxl_text_id' => $text2Id ] );
		$textInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $typeId, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang1Id = $this->db->insertId();

		$this->getCleaner()->cleanTermInLangIds( $this->db, $this->db, [ $termInLang1Id ] );

		// $text2Id and $textInLang2Id are not used by any term_in_lang,
		// but we should not attempt to clean them up
		$this->assertSelect(
			'wbt_text',
			'wbx_id',
			'*',
			[ [ $text2Id ] ]
		);
		$this->assertSelect(
			'wbt_text_in_lang',
			'wbxl_id',
			'*',
			[ [ $textInLang2Id ] ]
		);
	}

	public function testT237984_sharedTextInLangIdsAreNotDeleted() {
		$this->db->insert( 'wbt_type',
			[ 'wby_name' => 'label' ] );
		$typeIdLabel = $this->db->insertId();
		$this->db->insert( 'wbt_type',
			[ 'wby_name' => 'description' ] );
		$typeIdDescription = $this->db->insertId();

		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'someText' ] );
		$textId = $this->db->insertId();

		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'en', 'wbxl_text_id' => $textId ] );
		$textInLangIdSingleUse1 = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'de', 'wbxl_text_id' => $textId ] );
		$textInLangIdSingleUse2 = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'fr', 'wbxl_text_id' => $textId ] );
		$textInLangIdShared = $this->db->insertId();

		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $typeIdLabel, 'wbtl_text_in_lang_id' => $textInLangIdSingleUse1 ] );
		$termInLangIdToDelete1 = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $typeIdLabel, 'wbtl_text_in_lang_id' => $textInLangIdSingleUse2 ] );
		$termInLangIdToDelete2 = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $typeIdLabel, 'wbtl_text_in_lang_id' => $textInLangIdShared ] );
		$termInLangIdToDelete3 = $this->db->insertId();

		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $typeIdDescription, 'wbtl_text_in_lang_id' => $textInLangIdShared ] );
		$termInLangIdToRemain = $this->db->insertId();

		$this->getCleaner()->cleanTermInLangIds( $this->db, $this->db, [
			$termInLangIdToDelete1,
			$termInLangIdToDelete2,
			$termInLangIdToDelete3,
		] );

		$this->assertSelect(
			'wbt_term_in_lang',
			'wbtl_id',
			'*',
			[ [ $termInLangIdToRemain ] ]
		);
		// This row should not be deleted, as it is still used by $termInLangIdToRemain
		$this->assertSelect(
			'wbt_text_in_lang',
			'wbxl_id',
			'*',
			[ [ $textInLangIdShared ] ]
		);
	}

	public function testT237984_sharedTextIdsAreNotDeleted() {
		$this->db->insert( 'wbt_type',
			[ 'wby_name' => 'label' ] );
		$typeIdLabel = $this->db->insertId();

		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'someText1' ] );
		$textIdSingleUse = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'someText2' ] );
		$textIdShared = $this->db->insertId();

		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'en', 'wbxl_text_id' => $textIdSingleUse ] );
		$textInLangIdToDelete1 = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'de', 'wbxl_text_id' => $textIdShared ] );
		$textInLangIdToDelete2 = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'fr', 'wbxl_text_id' => $textIdShared ] );
		$textInLangIdToRemain3 = $this->db->insertId();

		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $typeIdLabel, 'wbtl_text_in_lang_id' => $textInLangIdToDelete1 ] );
		$termInLangIdToDelete1 = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $typeIdLabel, 'wbtl_text_in_lang_id' => $textInLangIdToDelete2 ] );
		$termInLangIdToDelete2 = $this->db->insertId();

		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $typeIdLabel, 'wbtl_text_in_lang_id' => $textInLangIdToRemain3 ] );
		$termInLangIdToRemain3 = $this->db->insertId();

		$this->getCleaner()->cleanTermInLangIds( $this->db, $this->db, [ $termInLangIdToDelete1, $termInLangIdToDelete2 ] );

		$this->assertSelect(
			'wbt_term_in_lang',
			'wbtl_id',
			'*',
			[ [ $termInLangIdToRemain3 ] ]
		);
		// This row should not be deleted, as it is still used by $textIdShared
		$this->assertSelect(
			'wbt_text',
			'wbx_id',
			'*',
			[ [ $textIdShared ] ]
		);
	}

}
