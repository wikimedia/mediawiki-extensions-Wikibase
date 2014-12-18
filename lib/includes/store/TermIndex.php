<?php

namespace Wikibase;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Interface to a cache for terms with both write and lookup methods.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface TermIndex {

	/**
	 * Saves the terms of the provided entity in the term cache.
	 *
	 * @since 0.1
	 *
	 * @param EntityDocument $entity
	 *
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
	 *        or "alias". Compare the Term::TYPE_XXX constants. If null, all types are returned.
	 * @param string[]|null $languageCodes The desired languages, given as language codes.
	 *        If null, all languages are returned.
	 *
	 * @return Term[]
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
	 *        or "alias". Compare the Term::TYPE_XXX constants. If null, all types are returned.
	 * @param string[]|null $languageCodes The desired languages, given as language codes.
	 *        If null, all languages are returned.
	 *
	 * @return Term[]
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
	 * termType, termLanguage, termText are all set.
	 *
	 * @since 0.2
	 *
	 * @param Term[] $terms
	 * @param string|null $termType
	 * @param string|null $entityType
	 * @param array $options
	 *        Accepted options are:
	 *        - caseSensitive: boolean, default true
	 *        - prefixSearch: boolean, default false
	 *        - LIMIT: int, defaults to none
	 *
	 * @return Term[]
	 */
	public function getMatchingTerms(
		array $terms,
		$termType = null,
		$entityType = null,
		array $options = array()
	);

	/**
	 * Returns the IDs that match the provided conditions.
	 *
	 * $terms is an array of Term objects. Terms are joined by OR.
	 * The fields of the terms are joined by AND.
	 *
	 * A single entityType has to be provided.
	 *
	 * @since 0.4
	 *
	 * @param Term[] $terms
	 * @param string|null $entityType
	 * @param array $options
	 *        Accepted options are:
	 *        - caseSensitive: boolean, default true
	 *        - prefixSearch: boolean, default false
	 *        - LIMIT: int, defaults to none
	 *
	 * @return EntityId[]
	 */
	public function getMatchingIDs( array $terms, $entityType = null, array $options = array() );


	/**
	 * Clears all terms from the cache.
	 *
	 * @since 0.2
	 *
	 * @return boolean Success indicator
	 */
	public function clear();

}
