<?php

declare( strict_types=1 );
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

		[ $text1Id, $text2Id ] = $this->insertTexts( [ 'a label', 'eine Bezeichnung' ] );
		[ $textInLang1Id, $textInLang2Id ] = $this->insertTextsInLang( [ $text1Id => 'en', $text2Id => 'de' ] );
		[ $termInLang1Id, $termInLang2Id ] = $this->insertTermsInLang( [ $textInLang1Id => $typeId, $textInLang2Id => $typeId ] );

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

		// insert two texts into wbt_text
		[ $text1Id, $text2Id ] = $this->insertTexts( [ 'some text', 'etwas Text' ] );

		// insert into wbt_text_in_lang
		[ $textInLang1Id, $textInLang2Id ] = $this->insertTextsInLang( [ $text1Id => 'en', $text2Id => 'de' ] );

		// both texts are label & description in wbt_term_in_lang
		[ $termInLang1Id, $termInLang2Id, $termInLang3Id, $termInLang4Id ] = $this->insertTermsInLang(
			[
				$textInLang1Id => [ $type1Id, $type2Id ],
				$textInLang2Id => [ $type1Id, $type2Id ],
			]
		);

		// remove the first and the last one
		$this->getCleaner()->cleanTermInLangIds( $this->db, $this->db, [ $termInLang1Id, $termInLang4Id ] );

		// The two initial inserts remain
		$this->assertTextTableReturns( [ $text1Id, $text2Id ] );
		$this->assertTextInLangTableReturns( [ $textInLang1Id, $textInLang2Id ] );

		// the first and the last is removed from wbt_term_in_lang
		$this->assertTermInLangTableReturns( [ $termInLang2Id, $termInLang3Id ] );
	}

	public function testCleanupOneTextInLangButNoText() {
		$this->db->insert( 'wbt_type',
			[ 'wby_name' => 'label' ] );
		$typeId = $this->db->insertId();

		// insert two texts into wbt_text
		[ $text1Id, $text2Id ] = $this->insertTexts( [ 'text', 'Text' ] );

		// insert into wbt_text_in_lang
		[ $textInLang1Id, $textInLang2Id ] = $this->insertTextsInLang( [ $text1Id => 'en', $text2Id => 'de' ] );
		// text1 has one additional
		[ $textInLang3Id ] = $this->insertTextsInLang( [ $text1Id => 'fr' ] );

		// all terms are the same type
		[ $termInLang1Id, $termInLang2Id, $termInLang3Id ] = $this->insertTermsInLang(
			[
				$textInLang1Id => $typeId,
				$textInLang2Id => $typeId,
				$textInLang3Id => $typeId,
			]
		);

		// remove term_in_lang with 'en' language
		$this->getCleaner()->cleanTermInLangIds( $this->db, $this->db, [ $termInLang1Id ] );

		// $textInLang1Id and $termInLang1Id gone,
		$this->assertTextInLangTableReturns( [ $textInLang2Id, $textInLang3Id ] );
		$this->assertTermInLangTableReturns( [ $termInLang2Id, $termInLang3Id ] );

		// but $text1Id is still there because referenced by $termInLang3Id
		$this->assertTextTableReturns( [ $text1Id, $text2Id ] );
	}

	public function testCleanupOneText() {
		$this->db->insert( 'wbt_type',
			[ 'wby_name' => 'label' ] );
		$typeId = $this->db->insertId();

		// insert two texts into wbt_text
		[ $text1Id, $text2Id ] = $this->insertTexts( [ 'text', 'Text' ] );

		// insert into wbt_text_in_lang and term_in_lang
		[ $textInLang1Id, $textInLang2Id ] = $this->insertTextsInLang( [ $text1Id => 'en', $text2Id => 'de' ] );
		[ $termInLang1Id, $termInLang2Id ] = $this->insertTermsInLang( [ $textInLang1Id => $typeId, $textInLang2Id => $typeId ] );

		$this->getCleaner()->cleanTermInLangIds( $this->db, $this->db, [ $termInLang1Id ] );

		// $textId1, $textInLang1Id and $termInLang1Id gone
		$this->assertTextTableReturns( [ $text2Id ] );
		$this->assertTextInLangTableReturns( [ $textInLang2Id ] );
		$this->assertTermInLangTableReturns( [ $termInLang2Id ] );
	}

	public function testCleanupLeavesUnrelatedTextsUntouched() {
		$this->db->insert( 'wbt_type',
			[ 'wby_name' => 'label' ] );
		$typeId = $this->db->insertId();

		[ $text1Id, $text2Id ] = $this->insertTexts( [ 'a label', 'eine Bezeichnung' ] );
		[ $textInLang1Id, $textInLang2Id ] = $this->insertTextsInLang( [ $text1Id => 'en', $text2Id => 'de' ] );
		[ $termInLang1Id ] = $this->insertTermsInLang( [ $textInLang1Id => $typeId ] );

		// remove the first
		$this->getCleaner()->cleanTermInLangIds( $this->db, $this->db, [ $termInLang1Id ] );

		// $text2Id and $textInLang2Id are not used by any term_in_lang,
		// but we should not attempt to clean them up
		$this->assertTextTableReturns( [ $text2Id ] );
		$this->assertTextInLangTableReturns( [ $textInLang2Id ] );
		$this->assertTermInLangTableReturns( [] );
	}

	public function testT237984_sharedTextInLangIdsAreNotDeleted() {
		$this->db->insert( 'wbt_type',
			[ 'wby_name' => 'label' ] );
		$typeIdLabel = $this->db->insertId();

		$this->db->insert( 'wbt_type',
			[ 'wby_name' => 'description' ] );
		$typeIdDescription = $this->db->insertId();

		[ $textId ] = $this->insertTexts( [ 'someText' ] );

		[ $textInLangIdSingleUse1 ] = $this->insertTextsInLang( [ $textId => 'en' ] );
		[ $textInLangIdSingleUse2 ] = $this->insertTextsInLang( [ $textId => 'de' ] );
		[ $textInLangIdShared ] = $this->insertTextsInLang( [ $textId => 'fr' ] );

		[ $termInLangIdToDelete1, $termInLangIdToDelete2, $termInLangIdToDelete3, $termInLangIdToRemain ] = $this->insertTermsInLang(
			[
				$textInLangIdSingleUse1 => $typeIdLabel,
				$textInLangIdSingleUse2 => $typeIdLabel,
				$textInLangIdShared => [ $typeIdLabel, $typeIdDescription ],
			]
		);

		$this->getCleaner()->cleanTermInLangIds( $this->db, $this->db, [
			$termInLangIdToDelete1,
			$termInLangIdToDelete2,
			$termInLangIdToDelete3,
		] );

		$this->assertTextTableReturns( [ $textId ] );
		$this->assertTermInLangTableReturns( [ $termInLangIdToRemain ] );
		// This row should not be deleted, as it is still used by $termInLangIdToRemain
		$this->assertTextInLangTableReturns( [ $textInLangIdShared ] );
	}

	public function testT237984_sharedTextIdsAreNotDeleted() {
		$this->db->insert( 'wbt_type',
			[ 'wby_name' => 'label' ] );
		$typeIdLabel = $this->db->insertId();

		[ $textIdSingleUse, $textIdShared ] = $this->insertTexts( [ 'someText1', 'someText2' ] );

		// insert a language for each, and one additional for the shared
		[ $textInLangIdToDelete1, $textInLangIdToDelete2 ] = $this->insertTextsInLang(
			[
				$textIdSingleUse => 'en',
				$textIdShared => 'de',
			]
		);
		[ $textInLangIdToRemain3 ] = $this->insertTextsInLang( [ $textIdShared => 'fr' ] );

		[ $termInLangIdToDelete1, $termInLangIdToDelete2, $termInLangIdToRemain3 ] = $this->insertTermsInLang(
			[
				$textInLangIdToDelete1 => $typeIdLabel,
				$textInLangIdToDelete2 => $typeIdLabel,
				$textInLangIdToRemain3 => $typeIdLabel,
			]
		);

		$this->getCleaner()->cleanTermInLangIds( $this->db, $this->db, [ $termInLangIdToDelete1, $termInLangIdToDelete2 ] );

		// This row should not be deleted, as it is still used by $textIdShared
		$this->assertTermInLangTableReturns( [ $termInLangIdToRemain3 ] );
		$this->assertTextTableReturns( [ $textIdShared ] );
	}

	private function assertTableReturns( array $elements, string $table, string $field ) {
		$this->assertSelect(
			$table,
			$field,
			'*',
			array_map(
				function( $element ) {
					return [ $element ];
				},
				$elements
			),
			[ 'ORDER BY' => $field ]
		);
	}

	private function assertTextTableReturns( array $elements ) {
		$this->assertTableReturns( $elements, 'wbt_text', 'wbx_id' );
	}

	private function assertTextInLangTableReturns( array $elements ) {
		$this->assertTableReturns( $elements, 'wbt_text_in_lang', 'wbxl_id' );
	}

	private function assertTermInLangTableReturns( array $elements ) {
		$this->assertTableReturns( $elements, 'wbt_term_in_lang', 'wbtl_id' );
	}

	/**
	 * @param string[] $texts list of texts
	 * @return int[] text IDs in the same order
	 */
	private function insertTexts( array $texts ): array {
		$ids = [];
		foreach ( $texts as $text ) {
			$this->db->insert( 'wbt_text',
				[ 'wbx_text' => $text ] );
			$ids[] = $this->db->insertId();
		}
		return $ids;
	}

	/**
	 * @param string[] $textIds mapping from text ID to language code
	 * @return int[] text_in_lang IDs in the same order
	 */
	private function insertTextsInLang( array $textIds ): array {
		$ids = [];
		foreach ( $textIds as $textId => $language ) {
			$this->db->insert( 'wbt_text_in_lang',
				[ 'wbxl_language' => $language, 'wbxl_text_id' => $textId ] );
			$ids[] = $this->db->insertId();
		}
		return $ids;
	}

	/**
	 * @param (int|int[])[] $textInLangIds mapping from text_in_lang ID to type ID(s)
	 * (to add several terms with the same text_in_lang ID, call the method multiple times)
	 * @return int[] term_in_lang IDs in the same order
	 */
	private function insertTermsInLang( array $textInLangIds ): array {
		$ids = [];
		foreach ( $textInLangIds as $textInLangId => $typeId ) {
			if ( !is_array( $typeId ) ) {
				$typeId = [ $typeId ];
			}
			foreach ( $typeId as $id ) {
				$this->db->insert( 'wbt_term_in_lang',
					[ 'wbtl_type_id' => $id, 'wbtl_text_in_lang_id' => $textInLangId ] );
				$ids[] = $this->db->insertId();
			}
		}
		return $ids;
	}

}
