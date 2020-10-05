<?php

namespace Wikibase\DataAccess;

/**
 * Provides access to settings relevant for services in the data access component.
 *
 * @license GPL-2.0-or-later
 */
class DataAccessSettings {

	/**
	 * @var int
	 */
	private $maxSerializedEntitySizeInBytes;

	/**
	 * @var bool
	 */
	private $useSearchFields;

	/**
	 * @var bool
	 */
	private $forceWriteSearchFields;

	/**
	 * @param int $maxSerializedEntitySizeInKiloBytes
	 * @param bool $useSearchFields
	 * @param bool $forceWriteSearchFields
	 */
	public function __construct(
		int $maxSerializedEntitySizeInKiloBytes,
		bool $useSearchFields,
		bool $forceWriteSearchFields
	) {
		$this->maxSerializedEntitySizeInBytes = $maxSerializedEntitySizeInKiloBytes * 1024;
		$this->useSearchFields = $useSearchFields;
		$this->forceWriteSearchFields = $forceWriteSearchFields;
	}

	/**
	 * @return int
	 */
	public function maxSerializedEntitySizeInBytes() {
		return $this->maxSerializedEntitySizeInBytes;
	}

	/**
	 * @return bool
	 */
	public function useSearchFields() {
		return $this->useSearchFields;
	}

	/**
	 * @return bool
	 */
	public function forceWriteSearchFields() {
		return $this->forceWriteSearchFields;
	}

	/**
	 * Whether to read property terms from the new, normalized schema,
	 * rather than from the old wb_terms table.
	 *
	 * The new schema is accessed through classes like
	 * {@link DatabasePropertyTermStoreWriter} and {@link PrefetchingPropertyTermLookup},
	 * the old one through classes like {@link TermSqlIndex}
	 * and {@link BufferingTermIndexTermLookup}.
	 *
	 * This is a temporary setting used during the transition period.
	 * Eventually, the normalized schema will be the only one supported.
	 *
	 * @return bool Always true.
	 * @deprecated Will be completely removed soon.
	 */
	public function useNormalizedPropertyTerms() {
		return true;
	}

	/**
	 * Whether to read item terms from the new, normalized schema,
	 * rather than from the old wb_terms table.
	 *
	 * The new schema is accessed through classes like
	 * {@link DatabaseItemTermStoreWriter} and {@link PrefetchingItemTermLookup},
	 * the old one through classes like {@link TermSqlIndex}
	 * and {@link BufferingTermIndexTermLookup}.
	 *
	 * During migraiton of item terms, we have different stages on for different, subsequent ranges
	 * of item ids. That is why this method takes a numeric entity id.
	 *
	 * @return bool Always true.
	 * @deprecated Will be completely removed soon.
	 */
	public function useNormalizedItemTerms( int $numericItemId ): bool {
		return true;
	}

	/**
	 * @return array The mapping of maxId to migration stages of item terms
	 */
	public function getItemTermsMigrationStages(): array {
		return [ 'max' => MIGRATION_NEW ];
	}

}
