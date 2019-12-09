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
	 * @param  string $alias Alias for the entity terms table
	 *  This will also be used as a prefix for inner aliases in join
	 *  statements. The following aliases can be used externally to refer
	 *  to these tables in furthe SQL statements:
	 *  - {$alias}: alias for entity terms table (determined by $this->entityTermsTable)
	 *  - {$alias}TermInLang: alias for wbt_terms_in_lang table
	 *  - {$alias}TextInLang: alias for wbt_text_in_lang table
	 *  - {$alias}Text: alias for wbt_text table
	 * @return array with the following elements in order:
	 *  - table: the final table (array, including the joins)
	 *  - join conditions
	 *  - entity id column: the column name that contains the entity id within the top-most entity terms table
	 *  	this can be accessed inside other conditions as: "{$alias}.{$entityIdColumn}"
	 */
	public function getEntityTermsTableAndJoinConditions( string $alias ) {
		$table = [
			"{$alias}EntityTermsJoin" => [
				"{$alias}" => $this->entityTermsTable,
				"{$alias}TermInLangJoin" => [
					"{$alias}TermInLang" => 'wbt_term_in_lang',
					"{$alias}TextInLangJoin" => [
						"{$alias}TextInLang" => 'wbt_text_in_lang',
						"{$alias}TextJoin" => [ "{$alias}Text" => 'wbt_text' ]
					]
				]
			]
		];

		$joinConditions = [
			"{$alias}TextJoin" => [
				'JOIN',
				"{$alias}TextInLang.wbxl_text_id={$alias}Text.wbx_id"
			],
			"{$alias}TextInLangJoin" => [
				'JOIN',
				"{$alias}TermInLang.wbtl_text_in_lang_id={$alias}TextInLang.wbxl_id"
			],
			"{$alias}TermInLangJoin" => [
				'JOIN',
				"{$alias}.{$this->entityTermsJoinColumn}={$alias}TermInLang.wbtl_id"
			]
		];

		return [ $table, $joinConditions, $this->entityIdColumn ];
	}
}
