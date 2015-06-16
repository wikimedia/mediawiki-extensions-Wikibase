<?php

namespace Wikibase\Repo\Interactors;

/**
 * Interface for searching for terms
 *
 * @since 0.5
 * @author Adam Shorland
 */
interface TermSearchInterface {

	/**
	 * @param string $text Term text to search for
	 * @param string[] $languageCodes Language codes to search in
	 * @param string $entityType Type of Entity to return
	 * @param string[] $termTypes Types of Term to return
	 *
	 * @returns array[] array of arrays containing the following:
	 *          ['entityId'] => EntityId EntityId
	 *          ['matchedTerm'] => Term MatchedTerm
	 *          ['displayTerms'] => Term[]|null array with keys Wikibase\Term::TYPE_* or null
	 */
	public function searchForTerms( $text, array $languageCodes, $entityType, array $termTypes );

}
