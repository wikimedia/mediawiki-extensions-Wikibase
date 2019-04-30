<?php

use Wikibase\Lib\Store\Sql\MediaWikiTermStore\MediaWikiDatabaseEntityTermStore;
use Wikibase\WikibaseSettings;

/**
 * @group Wikibase
 * @group Database
 */
class MediaWikiDatabaseEntityTermStoreTest extends \MediaWikiTestCase {
	const TYPE_ID_LABEL = 1;
	const TYPE_ID_DESCRIPTION = 2;
	const TYPE_ID_ALIAS = 3;

	protected function setUp() {
		parent::setUp();

		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because a local wbt_* tables"
									. " are not available on a WikibaseClient only instance." );
		}

		$this->tablesUsed[] = 'wbt_item_terms';
		$this->tablesUsed[] = 'wbt_property_terms';
		$this->tablesUsed[] = 'wbt_term_in_lang';
		$this->tablesUsed[] = 'wbt_text';
		$this->tablesUsed[] = 'wbt_text_in_lang';
		$this->tablesUsed[] = 'wbt_type';

		$this->populateTypesTable();
	}

	private function populateTypesTable() {
		$this->db->insert(
			'wbt_type',
			[
				[ 'wby_name' => 'label', 'wby_id' => self::TYPE_ID_LABEL ],
				[ 'wby_name' => 'description', 'wby_id' => self::TYPE_ID_DESCRIPTION ],
				[ 'wby_name' => 'alias', 'wby_id' => self::TYPE_ID_ALIAS ]
			]
		);
	}

	private function getDatabaseAccess() {
		return new MediaWikiDatabaseEntityTermStore( $this->db );
	}

	public function testSetPropertyLabel() {
		$databaseAccess = $this->getDatabaseAccess();

		$databaseAccess->setPropertyLabel( 123, 'en', 'hello' );
		$databaseAccess->setPropertyLabel( 123, 'es', 'hola' );

		$databaseAccess->setPropertyLabel( 321, 'en', 'hi' );
		$databaseAccess->setPropertyLabel( 321, 'es', 'hola' );

		// we have only 3 unique texts: hello, hola and hi
		$textCount = $this->db->selectRowCount( 'wbt_text', [ '*' ] );
		$this->assertEquals( 3, $textCount );

		// we have only 3 recrods in text_in_lang table: en -> hello, en -> hi, es -> hola
		$textInLangCount = $this->db->selectRowCount( 'wbt_text_in_lang', [ '*' ] );
		$this->assertEquals( 3, $textInLangCount );

		// we have only 3 recrods in term_in_lang table: en -> hello, en -> hi, es -> hola
		$termInLangCount = $this->db->selectRowCount( 'wbt_term_in_lang', [ '*' ] );
		$this->assertEquals( 3, $termInLangCount );

		// we have 4 property terms, 2 for each (in 2 languages)
		$proeprtyTermsCount = $this->db->selectRowCount( 'wbt_property_terms', [ '*' ] );
		$this->assertEquals( 4, $proeprtyTermsCount );
	}

	public function testSetPropertyDescription() {
		$databaseAccess = $this->getDatabaseAccess();

		$databaseAccess->setPropertyDescription( 123, 'en', 'hello' );
		$databaseAccess->setPropertyDescription( 123, 'es', 'hola' );

		$databaseAccess->setPropertyDescription( 321, 'en', 'hi' );
		$databaseAccess->setPropertyDescription( 321, 'es', 'hola' );

		// we have only 3 unique texts: hello, hola and hi
		$textCount = $this->db->selectRowCount( 'wbt_text', [ '*' ] );
		$this->assertEquals( 3, $textCount );

		// we have only 3 recrods in text_in_lang table: en -> hello, en -> hi, es -> hola
		$textInLangCount = $this->db->selectRowCount( 'wbt_text_in_lang', [ '*' ] );
		$this->assertEquals( 3, $textInLangCount );

		// we have only 3 recrods in term_in_lang table: en -> hello, en -> hi, es -> hola
		$termInLangCount = $this->db->selectRowCount( 'wbt_term_in_lang', [ '*' ] );
		$this->assertEquals( 3, $termInLangCount );

		// we have 4 property terms, 2 for each (in 2 languages)
		$proeprtyTermsCount = $this->db->selectRowCount( 'wbt_property_terms', [ '*' ] );
		$this->assertEquals( 4, $proeprtyTermsCount );
	}

	public function testSetPropertyAlias() {
		$databaseAccess = $this->getDatabaseAccess();

		$databaseAccess->setPropertyAlias( 123, 'en', 'hello' );
		$databaseAccess->setPropertyAlias( 123, 'es', 'hola' );
		$databaseAccess->setPropertyAlias( 123, 'en', 'hi' );

		$databaseAccess->setPropertyAlias( 321, 'en', 'hey' );
		$databaseAccess->setPropertyAlias( 321, 'es', 'hola' );

		// we have only 4 unique texts: hello, hola, hey and hi
		$textCount = $this->db->selectRowCount( 'wbt_text', [ '*' ] );
		$this->assertEquals( 4, $textCount );

		// we have only 4 recrods in text_in_lang table: en -> hello, en -> hi, es -> hola
		$textInLangCount = $this->db->selectRowCount( 'wbt_text_in_lang', [ '*' ] );
		$this->assertEquals( 4, $textInLangCount );

		// we have only 4 recrods in term_in_lang table: en -> hello, en -> hi, es -> hola
		$termInLangCount = $this->db->selectRowCount( 'wbt_term_in_lang', [ '*' ] );
		$this->assertEquals( 4, $termInLangCount );

		// we have 6 property terms
		$proeprtyTermsCount = $this->db->selectRowCount( 'wbt_property_terms', [ '*' ] );
		$this->assertEquals( 5, $proeprtyTermsCount );
	}

	public function testClearPropertyTerms() {
		$this->insertTerm( self::TYPE_ID_LABEL, 'en', 'hi', 'property', 123 );
		$this->insertTerm( self::TYPE_ID_DESCRIPTION, 'en', 'hello', 'property', 123 );
		$this->insertTerm( self::TYPE_ID_ALIAS, 'en', 'hey', 'property', 123 );

		$this->getDatabaseAccess()->clearPropertyTerms( 123 );

		// we have no property terms
		$propertyTermsCount = $this->db->selectRowCount( 'wbt_property_terms', [ '*' ] );
		$this->assertEquals( 0, $propertyTermsCount );
	}

	private function insertTerm( $typeId, $lang, $text, $entityId = null, $entityType = null ) {
		$this->db->insert(
			'wbt_text',
			[ 'wbx_text' => $text ]
		);
		$textId = $this->db->selectRow(
			'wbt_text',
			[ 'wbx_id' ],
			[ 'wbx_text' => $text ]
		)->wbx_id;

		$this->db->insert(
			'wbt_text_in_lang',
			[ 'wbxl_text_id' => $textId, 'wbxl_language' => $lang ]
		);
		$textInLangId = $this->db->selectRow(
			'wbt_text_in_lang',
			[ 'wbxl_id' ],
			[ 'wbxl_text_id' => $textId, 'wbxl_language' => $lang ]
		)->wbxl_id;

		$this->db->insert(
			'wbt_term_in_lang',
			[ 'wbtl_text_in_lang_id' => $textInLangId, 'wbtl_type_id' => $typeId ]
		);
		$termInLangId = $this->db->selectRow(
			'wbt_term_in_lang',
			[ 'wbtl_id' ],
			[ 'wbtl_text_in_lang_id' => $textInLangId, 'wbtl_type_id' => $typeId ]
		)->wbtl_id;

		if ( $entityType === 'item' || $entityType === 'both' ) {
			$this->db->insert(
				'wbt_item_terms',
				[ 'wbit_term_in_lang_id' => $termInLangId, 'wbit_item_id' => $entityId ]
			);
		}

		if ($entityType === 'property' || $entityType === 'both' ) {
			$this->db->insert(
				'wbt_property_terms',
				[ 'wbpt_term_in_lang_id' => $termInLangId, 'wbpt_property_id' => $entityId ]
			);
		}
	}
}
