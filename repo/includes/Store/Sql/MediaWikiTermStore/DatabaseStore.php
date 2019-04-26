<?php

namespace Wikibase\Repo\Store\Sql\MediaWikiTermStore;

class DatabaseStore {
	const TERM_TYPE_LABEL = 'label';
	const TERM_TYPE_DESCRIPTION = 'description';
	const TERM_TYPE_ALIAS = 'alias';

	const TABLE_TYPE = 'type';
	const TABLE_TEXT = 'text';
	const TABLE_TEXT_IN_LANG = 'wbt_text_in_lang';
	const TABLE_TERM_IN_LANG = 'wbt_term_in_lang';
	const TABLE_PROPERTY_TERMS = 'wbt_property_terms';
	const TABLE_ITEM_TERMS = 'wbt_property_terms';

	const TABLE_PREFIX = 'wbt_';

	const COLUMN_PREFIX_TYPE = 'wby_';
	const COLUMN_PREFIX_TEXT = 'wbx_';
	const COLUMN_PREFIX_TEXT_IN_LANG = 'wxl_';
	const COLUMN_PREFIX_TERM_IN_LANG = 'wtl_';
	const COLUMN_PREFIX_PROPERTY_TERMS = 'wbpt_';
	const COLUMN_PREFIX_ITEM_TERMS = 'wbpt_';

	/**
	 * @var Database $db
	 */
	private $db;

	public function __construct( Database $db ) {
		$this->db = $db;
	}

	/* maybe smth like this, waiting on consumer implementation first

	public function setPropertyLabel( $propertyId, $lang, $label ) {
	}

	public function setPropertyDescription( $propertyId, $lang, $description ) {
	}
	...

	*/

	private function insertPropertyTerm( $propertyId, $type, $lang, $text ) {
		$termInLangId = $this->insertTerm( $type, $lang, $text );
		$propertyTermId = $this->insertPropertyTerm( $propertyId, $termInLangId );

		return $propertyTermId;
	}

	private function insertItemTerm( $itemId, $type, $lang, $text ) {
		$termInLangId = $this->insertTerm( $type, $lang, $text );
		$itemTermId = $this->insertItemTerm( $propertyId, $termInLangId );

		return $itemTermId;
	}

	private function insertTerm( $type, $lang, $text ) {
		$textId = $this->acquireTextId( $text );
		$textInLang = $this->acquireTextInLangId( $lang, $textId );
		$typeId = $this->acquireTypeId( $type );
		$termInLangId = $this->acquireTermInLang( $typeId, $textInLangId );

		return $termInLangId;
	}

	private function acquireTextId( $text ) {
		$this->db->insert(
			self::DB_TABLE_TEXT,
			[ 'wbx_text' => $text ],
			__METHOD__,
			[ 'IGNORE' => true ]
		);

		return $this->db->selectField(
			self::DB_TABLE_TEXT,
			'wbx_id',
			[ 'wbx_text' => $text ]
		);
	}

	private function acquireTextInLangId( $lang, $textId ) {
		$this->db->insert(
			self::DB_TABLE_TEXT_IN_LANG,
			[ 'wbxl_language' => $lang, 'wbxl_text_id' => $textId ],
			__METHOD__,
			[ 'IGNORE' => true ]
		);

		return $this->db->selectField(
			self::DB_TABLE_TEXT_IN_LANG,
			'wbxl_id',
			[ 'wbxl_language' => $lang, 'wbxl_text_id' => $textId ]
		);
	}

	private function acquireTypeId( $typeName ) {
		$this->db->insert(
			self::DB_TABLE_TYPE,
			[ 'wby_name' => $typeName ],
			__METHOD__,
			[ 'IGNORE' => true ]
		);

		return $this->db->selectField(
			self::DB_TABLE_TYPE,
			'wby_id',
			[ 'wby_name' => $typeName ]
		);
	}

	private function acquireTermInLang( $typeId, $textInLangId ) {
		$this->db->insert(
			self::DB_TABLE_TERM_IN_LANG,
			[
				'wbtl_type_id' => $typeId,
				'wbtl_text_in_lang_id' => $textInLangId
			],
			__METHOD__,
			[ 'IGNORE' => true ]
		);

		return $this->db->selectField(
			self::DB_TABLE_TERM_IN_LANG,
			'wbtl_id',
			[
				'wbtl_type_id' => $typeId,
				'wbtl_text_in_lang_id' => $textInLangId
			]
		);
	}

	private function insertProeprtyTerm( $propertyId, $termInLangId ) {
		$this->db->insert(
			self::DB_TABLE_PROPERTY_TERMS,
			[
				'wbpt_property_id' => $propertyId,
				'wbpt_term_in_lang_id' => $termInLangId
			]
		);
	}

	private function insertItemTerm( $itemId, $termInLangId ) {
		$this->db->insert(
			self::TABLE_ITEM_TERMS,
			[
				'wbit_item_id' => $itemId,
				'wbit_term_in_lang_id' => $termInLangId
			]
		);
	}

}
