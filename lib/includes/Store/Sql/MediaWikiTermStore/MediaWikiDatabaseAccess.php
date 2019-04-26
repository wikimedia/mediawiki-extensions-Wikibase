<?php

namespace Wikibase\Lib\Store\Sql\MediaWikiTermStore;

use Database;

class MediaWikiDatabaseAccess implements SchemaAccess {
	const ENTITY_TYPE_PROPERTY = 0x01;
	const ENTITY_TYPE_ITEM = 0x02;

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
	 * @var Database $dbMaster
	 */
	private $dbMaster;

	/**
	 * @var Database $dbReplica
	 */
	private $dbReplica;

	public function __construct( Database $dbMaster, Database $dbReplica ) {
		$this->dbMaster = $dbMaster;
		$this->dbReplica = $dbReplica;
	}

	/**
	 * @inheritdoc
	 */
	public function setPropertyTerms( $propertyId, array $termsArray) {
		$termInLangIds = $this->acquireTermInLangIds( $termsArray );

		$this->insertPropertyTerms( $propertyId, $termInLangIds[ 'persisted' ] );

		// do one of the following (ordered by favorability):
		// - pass this class a class that does encapsulates clean up triggering and call it here
		// - trigger an event with termInLangIds[ 'deleted'] if we have such support
		// - return termInLangIds[ 'deleted' ] so that job can be queued outside this class
		// - queue clean up job here and pass it termInLangIds[ 'deleted' ]
	}

	/**
	 * @inheritdoc
	 */
	public function clearPropertyTerms( $propertyId ) {
		$this->db->delete(
			self::PREFIX_TABLE . self::TABLE_PROPERTY_TERMS,
			[ 'wbpt_property_id' => $propertyId ]
		);
	}

	private function acquireTermInLangIds( $termsArray ) {
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

	private function insertPropertyTerms( $propertyId, $termInLangIds ) {
		$insertValues = array_map(
			$termInLangIds,
			function ( $termInLangId ) using( $propertyId ) {
				return [
					'wbpt_property_id' => $propertyId,
					'wbpt_term_in_lang_id' => $termInLangId
				];
			}
		);

		$this->db->insert(
			self::PREFIX_TABLE . self::TABLE_PROPERTY_TERMS,
			$insertValues,
			__METHOD__,
			[ 'IGNORE' ]
		);
	}

}
