<?php

namespace Wikibase\Lib\Store\Sql\MediaWikiTermStore;

use Database;

class MediaWikiDatabaseAccess implements SchemaAccess {
	const TERM_TYPE_LABEL = 'label';
	const TERM_TYPE_DESCRIPTION = 'description';
	const TERM_TYPE_ALIAS = 'alias';

	const PREFIX_TABLE = 'wbt_';

	const TABLE_TYPE = 'type';
	const TABLE_TEXT = 'text';
	const TABLE_TEXT_IN_LANG = 'text_in_lang';
	const TABLE_TERM_IN_LANG = 'term_in_lang';
	const TABLE_PROPERTY_TERMS = 'property_terms';
	const TABLE_ITEM_TERMS = 'property_terms';

	/**
	 * @var Database $db
	 */
	private $db;

	public function __construct( Database $db ) {
		$this->db = $db;
	}

	public function setPropertyLabel( $propertyId, $lang, $text ) {
		$this->setPropertyTerm(
			$propertyId,
			self::TERM_TYPE_LABEL,
			$lang,
			$text
		);
	}

	public function setPropertyDescription( $propertyId, $lang, $text ) {
		$this->setPropertyTerm(
			$propertyId,
			self::TERM_TYPE_DESCRIPTION,
			$lang,
			$text
		);
	}

	public function setPropertyAlias( $propertyId, $lang, $text ) {
		$this->setPropertyTerm(
			$propertyId,
			self::TERM_TYPE_ALIAS,
			$lang,
			$text
		);
	}

	public function clearPropertyTerms( $propertyId ) {
		$this->db->delete(
			self::PREFIX_TABLE . self::TABLE_PROPERTY_TERMS,
			[ 'wbpt_property_id' => $propertyId ]
		);
	}

	private function setPropertyTerm( $propertyId, $type, $lang, $text ) {
		$termInLangId = $this->insertTerm( $type, $lang, $text );
		$this->insertPropertyTerm( $propertyId, $termInLangId );
	}

	private function setItemTerm( $itemId, $type, $lang, $text ) {
		$termInLangId = $this->insertTerm( $type, $lang, $text );
		$itemTermId = $this->insertItemTerm( $propertyId, $termInLangId );

		return $itemTermId;
	}

	private function insertTerm( $type, $lang, $text ) {
		$textId = $this->acquireTextId( $text );
		$textInLangId = $this->acquireTextInLangId( $lang, $textId );
		$typeId = $this->acquireTypeId( $type );
		$termInLangId = $this->acquireTermInLang( $typeId, $textInLangId );

		return $termInLangId;
	}

	private function acquireTextId( $text ) {
		$this->db->insert(
			self::PREFIX_TABLE . self::TABLE_TEXT,
			[ 'wbx_text' => $text ],
			__METHOD__,
			[ 'IGNORE' ]
		);

		return $this->db->selectField(
			self::PREFIX_TABLE . self::TABLE_TEXT,
			'wbx_id',
			[ 'wbx_text' => $text ]
		);
	}

	private function acquireTextInLangId( $lang, $textId ) {
		$this->db->insert(
			self::PREFIX_TABLE . self::TABLE_TEXT_IN_LANG,
			[ 'wbxl_language' => $lang, 'wbxl_text_id' => $textId ],
			__METHOD__,
			[ 'IGNORE' ]
		);

		return $this->db->selectField(
			self::PREFIX_TABLE . self::TABLE_TEXT_IN_LANG,
			'wbxl_id',
			[ 'wbxl_language' => $lang, 'wbxl_text_id' => $textId ]
		);
	}

	private function acquireTypeId( $typeName ) {
		$this->db->insert(
			self::PREFIX_TABLE . self::TABLE_TYPE,
			[ 'wby_name' => $typeName ],
			__METHOD__,
			[ 'IGNORE' ]
		);

		return $this->db->selectField(
			self::PREFIX_TABLE . self::TABLE_TYPE,
			'wby_id',
			[ 'wby_name' => $typeName ]
		);
	}

	private function acquireTermInLang( $typeId, $textInLangId ) {
		$this->db->insert(
			self::PREFIX_TABLE . self::TABLE_TERM_IN_LANG,
			[
				'wbtl_type_id' => $typeId,
				'wbtl_text_in_lang_id' => $textInLangId
			],
			__METHOD__,
			[ 'IGNORE' ]
		);

		return $this->db->selectField(
			self::PREFIX_TABLE . self::TABLE_TERM_IN_LANG,
			'wbtl_id',
			[
				'wbtl_type_id' => $typeId,
				'wbtl_text_in_lang_id' => $textInLangId
			]
		);
	}

	private function insertPropertyTerm( $propertyId, $termInLangId ) {
		$this->db->insert(
			self::PREFIX_TABLE . self::TABLE_PROPERTY_TERMS,
			[
				'wbpt_property_id' => $propertyId,
				'wbpt_term_in_lang_id' => $termInLangId
			],
			__METHOD__,
			[ 'IGNORE' ]
		);
	}

	private function insertItemTerm( $itemId, $termInLangId ) {
		$this->db->insert(
			self::PREFIX_TABLE . self::TABLE_ITEM_TERMS,
			[
				'wbit_item_id' => $itemId,
				'wbit_term_in_lang_id' => $termInLangId
			],
			__METHOD__,
			[ 'IGNORE' ]
		);
	}

}
