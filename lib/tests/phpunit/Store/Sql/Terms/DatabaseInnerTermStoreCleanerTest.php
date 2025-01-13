<?php

declare( strict_types=1 );
namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use MediaWikiIntegrationTestCase;
use Wikibase\Lib\Store\Sql\Terms\DatabaseInnerTermStoreCleaner;
use Wikibase\Lib\Store\Sql\Terms\TermTypeIds;
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
	}

	private function getCleaner(): DatabaseInnerTermStoreCleaner {
		return new DatabaseInnerTermStoreCleaner();
	}

	public function testCleanupEverything() {
		[ $text1Id, $text2Id ] = $this->insertTexts( [ 'a label', 'eine Bezeichnung' ] );
		[ $textInLang1Id, $textInLang2Id ] = $this->insertTextsInLang( [ $text1Id => 'en', $text2Id => 'de' ] );
		[ $termInLang1Id, $termInLang2Id ] = $this->insertTermsInLang( [
			$textInLang1Id => TermTypeIds::LABEL_TYPE_ID,
			$textInLang2Id => TermTypeIds::LABEL_TYPE_ID,
		] );

		$this->getCleaner()->cleanTermInLangIds( $this->getDb(), $this->getDb(), [ $termInLang1Id, $termInLang2Id ] );

		$this->newSelectQueryBuilder()
			->select( 'wbx_id' )
			->from( 'wbt_text' )
			->assertEmptyResult();
		$this->newSelectQueryBuilder()
			->select( 'wbxl_id' )
			->from( 'wbt_text_in_lang' )
			->assertEmptyResult();
		$this->newSelectQueryBuilder()
			->select( 'wbtl_id' )
			->from( 'wbt_term_in_lang' )
			->assertEmptyResult();
	}

	public function testCleanupTermInLangButNoTextInLang() {
		// insert two texts into wbt_text
		[ $text1Id, $text2Id ] = $this->insertTexts( [ 'some text', 'etwas Text' ] );

		// insert into wbt_text_in_lang
		[ $textInLang1Id, $textInLang2Id ] = $this->insertTextsInLang( [ $text1Id => 'en', $text2Id => 'de' ] );

		// both texts are label & description in wbt_term_in_lang
		[ $termInLang1Id, $termInLang2Id, $termInLang3Id, $termInLang4Id ] = $this->insertTermsInLang(
			[
				$textInLang1Id => [ TermTypeIds::LABEL_TYPE_ID, TermTypeIds::DESCRIPTION_TYPE_ID ],
				$textInLang2Id => [ TermTypeIds::LABEL_TYPE_ID, TermTypeIds::DESCRIPTION_TYPE_ID ],
			]
		);

		// remove the first and the last one
		$this->getCleaner()->cleanTermInLangIds( $this->getDb(), $this->getDb(), [ $termInLang1Id, $termInLang4Id ] );

		// The two initial inserts remain
		$this->assertTextTableReturns( [ $text1Id, $text2Id ] );
		$this->assertTextInLangTableReturns( [ $textInLang1Id, $textInLang2Id ] );

		// the first and the last is removed from wbt_term_in_lang
		$this->assertTermInLangTableReturns( [ $termInLang2Id, $termInLang3Id ] );
	}

	public function testCleanupOneTextInLangButNoText() {
		// insert two texts into wbt_text
		[ $text1Id, $text2Id ] = $this->insertTexts( [ 'text', 'Text' ] );

		// insert into wbt_text_in_lang
		[ $textInLang1Id, $textInLang2Id ] = $this->insertTextsInLang( [ $text1Id => 'en', $text2Id => 'de' ] );
		// text1 has one additional
		[ $textInLang3Id ] = $this->insertTextsInLang( [ $text1Id => 'fr' ] );

		// all terms are the same type
		[ $termInLang1Id, $termInLang2Id, $termInLang3Id ] = $this->insertTermsInLang(
			[
				$textInLang1Id => TermTypeIds::LABEL_TYPE_ID,
				$textInLang2Id => TermTypeIds::LABEL_TYPE_ID,
				$textInLang3Id => TermTypeIds::LABEL_TYPE_ID,
			]
		);

		// remove term_in_lang with 'en' language
		$this->getCleaner()->cleanTermInLangIds( $this->getDb(), $this->getDb(), [ $termInLang1Id ] );

		// $textInLang1Id and $termInLang1Id gone,
		$this->assertTextInLangTableReturns( [ $textInLang2Id, $textInLang3Id ] );
		$this->assertTermInLangTableReturns( [ $termInLang2Id, $termInLang3Id ] );

		// but $text1Id is still there because referenced by $termInLang3Id
		$this->assertTextTableReturns( [ $text1Id, $text2Id ] );
	}

	public function testCleanupOneText() {
		// insert two texts into wbt_text
		[ $text1Id, $text2Id ] = $this->insertTexts( [ 'text', 'Text' ] );

		// insert into wbt_text_in_lang and term_in_lang
		[ $textInLang1Id, $textInLang2Id ] = $this->insertTextsInLang( [ $text1Id => 'en', $text2Id => 'de' ] );
		[ $termInLang1Id, $termInLang2Id ] = $this->insertTermsInLang( [
			$textInLang1Id => TermTypeIds::LABEL_TYPE_ID,
			$textInLang2Id => TermTypeIds::LABEL_TYPE_ID,
		] );

		$this->getCleaner()->cleanTermInLangIds( $this->getDb(), $this->getDb(), [ $termInLang1Id ] );

		// $textId1, $textInLang1Id and $termInLang1Id gone
		$this->assertTextTableReturns( [ $text2Id ] );
		$this->assertTextInLangTableReturns( [ $textInLang2Id ] );
		$this->assertTermInLangTableReturns( [ $termInLang2Id ] );
	}

	public function testCleanupLeavesUnrelatedTextsUntouched() {
		[ $text1Id, $text2Id ] = $this->insertTexts( [ 'a label', 'eine Bezeichnung' ] );
		[ $textInLang1Id, $textInLang2Id ] = $this->insertTextsInLang( [ $text1Id => 'en', $text2Id => 'de' ] );
		[ $termInLang1Id ] = $this->insertTermsInLang( [ $textInLang1Id => TermTypeIds::LABEL_TYPE_ID ] );

		// remove the first
		$this->getCleaner()->cleanTermInLangIds( $this->getDb(), $this->getDb(), [ $termInLang1Id ] );

		// $text2Id and $textInLang2Id are not used by any term_in_lang,
		// but we should not attempt to clean them up
		$this->assertTextTableReturns( [ $text2Id ] );
		$this->assertTextInLangTableReturns( [ $textInLang2Id ] );
		$this->assertTermInLangTableReturns( [] );
	}

	public function testT237984_sharedTextInLangIdsAreNotDeleted() {
		[ $textId ] = $this->insertTexts( [ 'someText' ] );

		[ $textInLangIdSingleUse1 ] = $this->insertTextsInLang( [ $textId => 'en' ] );
		[ $textInLangIdSingleUse2 ] = $this->insertTextsInLang( [ $textId => 'de' ] );
		[ $textInLangIdShared ] = $this->insertTextsInLang( [ $textId => 'fr' ] );

		[ $termInLangIdToDelete1, $termInLangIdToDelete2, $termInLangIdToDelete3, $termInLangIdToRemain ] = $this->insertTermsInLang(
			[
				$textInLangIdSingleUse1 => TermTypeIds::LABEL_TYPE_ID,
				$textInLangIdSingleUse2 => TermTypeIds::LABEL_TYPE_ID,
				$textInLangIdShared => [ TermTypeIds::LABEL_TYPE_ID, TermTypeIds::DESCRIPTION_TYPE_ID ],
			]
		);

		$this->getCleaner()->cleanTermInLangIds( $this->getDb(), $this->getDb(), [
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
				$textInLangIdToDelete1 => TermTypeIds::LABEL_TYPE_ID,
				$textInLangIdToDelete2 => TermTypeIds::LABEL_TYPE_ID,
				$textInLangIdToRemain3 => TermTypeIds::LABEL_TYPE_ID,
			]
		);

		$this->getCleaner()->cleanTermInLangIds( $this->getDb(), $this->getDb(), [ $termInLangIdToDelete1, $termInLangIdToDelete2 ] );

		// This row should not be deleted, as it is still used by $textIdShared
		$this->assertTermInLangTableReturns( [ $termInLangIdToRemain3 ] );
		$this->assertTextTableReturns( [ $textIdShared ] );
	}

	private function assertTableReturns( array $elements, string $table, string $field ) {
		$this->newSelectQueryBuilder()
			->select( $field )
			->from( $table )
			->assertResultSet( array_map(
				function( $element ) {
					return [ $element ];
				},
				$elements
			) );
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
			$this->getDb()->newInsertQueryBuilder()
				->insertInto( 'wbt_text' )
				->row( [ 'wbx_text' => $text ] )
				->caller( __METHOD__ )
				->execute();
			$ids[] = $this->getDb()->insertId();
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
			$this->getDb()->newInsertQueryBuilder()
				->insertInto( 'wbt_text_in_lang' )
				->row( [ 'wbxl_language' => $language, 'wbxl_text_id' => $textId ] )
				->caller( __METHOD__ )
				->execute();
			$ids[] = $this->getDb()->insertId();
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
				$this->getDb()->newInsertQueryBuilder()
					->insertInto( 'wbt_term_in_lang' )
					->row( [ 'wbtl_type_id' => $id, 'wbtl_text_in_lang_id' => $textInLangId ] )
					->caller( __METHOD__ )
					->execute();
				$ids[] = $this->getDb()->insertId();
			}
		}
		return $ids;
	}

}
