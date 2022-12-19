<?php

namespace Wikibase\Repo\Store\Sql\Terms;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;

/**
 * Helper to prepare joins necessary to select terms from new term store in db
 *
 * @license GPL-2.0-or-later
 */
class DatabaseEntityTermsTableProvider {

	/** @var string */
	private $entityTermsTable;

	/** @var string */
	private $entityTermsJoinColumn;

	/** @var string */
	private $entityIdColumn;

	/**
	 * @param string $entityType one of the supported types: Item::ENTITY_TYPE or Property::ENTITY_TYPE
	 */
	public function __construct( string $entityType ) {
		if ( $entityType === Item::ENTITY_TYPE ) {
			$this->entityTermsTable = 'wbt_item_terms';
			$this->entityTermsJoinColumn = 'wbit_term_in_lang_id';
			$this->entityIdColumn = 'wbit_item_id';
		} elseif ( $entityType === Property::ENTITY_TYPE ) {
			$this->entityTermsTable = 'wbt_property_terms';
			$this->entityTermsJoinColumn = 'wbpt_term_in_lang_id';
			$this->entityIdColumn = 'wbpt_property_id';
		} else {
			throw new InvalidArgumentException( "'{$entityType}' is not supported." );
		}
	}

	/**
	 * Constructs a table along the necessary joins with new store tables for selecting
	 * entity terms referenced in $entityTermsTable through $entityTermsJoinColumn column.
	 * @return array with the following elements in order:
	 *  - table: the final table (array, including the joins)
	 *  - join conditions
	 *  - entity id column: the column name that contains the entity id within the top-most entity terms table
	 */
	public function getEntityTermsTableAndJoinConditions() {
		$table = [
			$this->entityTermsTable,
			'wbt_term_in_lang',
			'wbt_text_in_lang',
			'wbt_text',
		];

		$joinConditions = [
			"wbt_term_in_lang" => [
				'JOIN',
				"{$this->entityTermsJoinColumn}=wbtl_id",
			],
			"wbt_text_in_lang" => [
				'JOIN',
				"wbtl_text_in_lang_id=wbxl_id",
			],
			"wbt_text" => [
				'JOIN',
				"wbxl_text_id=wbx_id",
			],
		];

		return [ $table, $joinConditions, $this->entityIdColumn ];
	}
}
