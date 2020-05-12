<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\TermIndexEntry;

/**
 * Methods factored out of TermIndex during the migration away from wb_terms.
 * This interface is old and poorly shaped, but not deprecated at this stage.
 *
 * @deprecated As wb_terms is going away, See https://phabricator.wikimedia.org/T208425
 *
 * @license GPL-2.0-or-later
 */
interface LegacyEntityTermStoreReader {

	/**
	 * Returns the terms stored for the given entity.
	 *
	 * @param EntityId $entityId
	 * @param string[]|null $termTypes The types of terms to return, e.g. "label", "description",
	 *        or "alias". Compare the TermIndexEntry::TYPE_XXX constants. If null, all types are returned.
	 * @param string[]|null $languageCodes The desired languages, given as language codes.
	 *        If null, all languages are returned.
	 *
	 * @return TermIndexEntry[]
	 */
	public function getTermsOfEntity(
		EntityId $entityId,
		array $termTypes = null,
		array $languageCodes = null
	);

	/**
	 * Returns the terms stored for the given entities. Can be filtered by language.
	 * Note that all entities queried in one call must be of the same type.
	 *
	 * @param EntityId[] $entityIds Entity ids of one type only.
	 * @param string[]|null $termTypes The types of terms to return, e.g. "label", "description",
	 *        or "alias". Compare the TermIndexEntry::TYPE_XXX constants. If null, all types are returned.
	 * @param string[]|null $languageCodes The desired languages, given as language codes.
	 *        If null, all languages are returned.
	 *
	 * @return TermIndexEntry[]
	 */
	public function getTermsOfEntities(
		array $entityIds,
		array $termTypes = null,
		array $languageCodes = null
	);

}
