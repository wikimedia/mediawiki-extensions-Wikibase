<?php

namespace Wikibase;

use Wikibase\Lib\Store\LabelConflictFinder;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Interface to a cache for terms with both write and lookup methods.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface TermIndex extends LabelConflictFinder {

	/**
	 * Returns the type, id tuples for the entities with the provided label in the specified language.
	 *
	 * @since 0.1
	 *
	 * @param string $label
	 * @param string|null $languageCode
	 * @param string|null $entityType
	 * @param bool $fuzzySearch if false, only exact matches are returned, otherwise more relaxed search . Defaults to false.
	 *
	 * @return EntityId[]
	 *
	 * TODO: update to use Term interface
	 */
	public function getEntityIdsForLabel( $label, $languageCode = null, $entityType = null, $fuzzySearch = false );

	/**
	 * Saves the terms of the provided entity in the term cache.
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 *
	 * @return boolean Success indicator
	 */
	public function saveTermsOfEntity( Entity $entity );

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
	 *
	 * @return Term[]
	 */
	public function getTermsOfEntity( EntityId $entityId );

	/**
	 * Returns the terms stored for the given entities. Can be filtered by language.
	 * Note that the entities must all be of the given type.
	 *
	 * @since 0.4
	 *
	 * @param EntityId[] $ids
	 * @param string $entityType
	 * @param string|null $language language code
	 *
	 * @return Term[]
	 */
	public function getTermsOfEntities( array $ids, $entityType, $language = null );

	/**
	 * Returns if a term with the specified parameters exists.
	 *
	 * @since 0.1
	 *
	 * @param string $termValue
	 * @param string|null $termType
	 * @param string|null $termLanguage Language code
	 * @param string|null $entityType
	 *
	 * @return boolean
	 */
	public function termExists( $termValue, $termType = null, $termLanguage = null, $entityType = null );

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
	public function getMatchingTerms( array $terms, $termType = null, $entityType = null, array $options = array() );

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
	 * @param string $entityType
	 * @param array $options
	 *        Accepted options are:
	 *        - caseSensitive: boolean, default true
	 *        - prefixSearch: boolean, default false
	 *        - LIMIT: int, defaults to none
	 *
	 * @return EntityId[]
	 */
	public function getMatchingIDs( array $terms, $entityType, array $options = array() );


	/**
	 * Clears all terms from the cache.
	 *
	 * @since 0.2
	 *
	 * @return boolean Success indicator
	 */
	public function clear();

}
