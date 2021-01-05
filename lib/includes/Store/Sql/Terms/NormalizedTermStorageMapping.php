<?php

declare( strict_types=1 );
namespace Wikibase\Lib\Store\Sql\Terms;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;

/**
 * Helper for mapping columns names of item/property in term storage
 *
 * @see DatabaseTermStoreWriterBase
 * @license GPL-2.0-or-later
 */
class NormalizedTermStorageMapping {

	/** @var string */
	protected $tablePrefix;
	/** @var string */
	protected $entityType;
	/** @var string */
	private $tableName;

	public function __construct( string $tablePrefix, string $tableName, string $entityType ) {
		$this->entityType = $entityType;
		$this->tableName = $tableName;
		$this->tablePrefix = $tablePrefix;
	}

	/**
	 * Returns the name of the entity reference column
	 *
	 * @return string e.g. wbit_item_id / wbpt_property_id
	 */
	public function getEntityIdColumn(): string {
		return $this->tablePrefix . '_' . $this->entityType . '_id';
	}

	/**
	 * Returns the name of the id column
	 *
	 * @return string e.g. wbit_id / wbpt_id
	 */
	public function getRowIdColumn(): string {
		return $this->tablePrefix . '_id';
	}

	/**
	 * Returns the name of the <entity>_term_in_lang id column
	 *
	 * @return string e.g. wbit_term_in_lang_id / wbpt_term_in_lang_id
	 */
	public function getTermInLangIdColumn(): string {
		return $this->tablePrefix . '_term_in_lang_id';
	}

	public function getTableName(): string {
		return $this->tableName;
	}

	public static function factory( string $entityType ): self {
		switch ( $entityType ) {
			case Item::ENTITY_TYPE:
				return new self( 'wbit', 'wbt_item_terms', $entityType );
			case Property::ENTITY_TYPE:
				return new self( 'wbpt', 'wbt_property_terms', $entityType );
			default:
				throw new InvalidArgumentException( 'Unsupported entity type: ' . $entityType );
		}
	}
}
