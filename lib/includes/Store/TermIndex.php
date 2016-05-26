<?php

namespace Wikibase;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Interface to a cache for terms with both write and lookup methods.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface TermIndex {

	/**
	 * Saves the terms of the provided entity in the term cache.
	 *
	 * @since 0.1
	 *
	 * @param EntityDocument $entity Must have an ID, and optionally any combination of terms as
	 *  declared by the TermIndexEntry::TYPE_... constants.
	 *
	 * @throws InvalidArgumentException when $entity does not have an ID.
	 * @return boolean Success indicator
	 */
	public function saveTermsOfEntity( EntityDocument $entity );

	/**
	 * Deletes the terms of the provided entity from the term cache.
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId
	 *
	 * @return boolean Success indicator
	 */
	public function deleteTermsOfEntity( EntityId $entityId );

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
	 * @since 0.4
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

	/**
	 * Returns the terms that match the provided conditions.
	 *
	 * $terms is an array of Term objects. Terms are joined by OR.
	 * The fields of the terms are joined by AND.
	 *
	 * A default can be provided for termType and entityType via the corresponding
	 * method parameters.
	 *
	 * The return value is an array of Terms where entityId, entityType,
	 * termType, termLanguage, termText, termWeight are all set.
	 *
	 * @since 0.2
	 *
	 * @param TermIndexEntry[] $terms
	 * @param string|string[]|null $termType
	 * @param string|string[]|null $entityType
	 * @param array $options
	 *        Accepted options are:
	 *        - caseSensitive: boolean, default true
	 *        - prefixSearch: boolean, default false
	 *        - LIMIT: int, defaults to none
	 *        - orderByWeight: boolean, default false
	 *
	 * @return TermIndexEntry[]
	 */
	public function getMatchingTerms(
		array $terms,
		$termType = null,
		$entityType = null,
		array $options = array()
	);

	/**
	 * Returns the terms that match the provided conditions ranked with the 'most important' / top first.
	 * Will only return one TermIndexEntry per Entity
	 *
	 * $terms is an array of Term objects. Terms are joined by OR.
	 * The fields of the terms are joined by AND.
	 *
	 * A default can be provided for termType and entityType via the corresponding
	 * method parameters.
	 *
	 * The return value is an array of Terms where entityId, entityType,
	 * termType, termLanguage, termText, termWeight are all set.
	 *
	 * @since 0.5
	 *
	 * @param TermIndexEntry[] $terms
	 * @param string|string[]|null $termType
	 * @param string|string[]|null $entityType
	 * @param array $options
	 *        Accepted options are:
	 *        - caseSensitive: boolean, default true
	 *        - prefixSearch: boolean, default false
	 *        - LIMIT: int, defaults to none
	 *
	 * @return TermIndexEntry[]
	 */
	public function getTopMatchingTerms(
		array $terms,
		$termType = null,
		$entityType = null,
		array $options = array()
	);

	/**
	 * Clears all terms from the cache.
	 *
	 * @since 0.2
	 *
	 * @return boolean Success indicator
	 */
	public function clear();

}
