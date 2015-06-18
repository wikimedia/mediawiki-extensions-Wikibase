<?php

namespace Wikibase\Repo\Interactors;

/**
 * Interface for searching for terms
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
interface TermSearchInteractor {

	/**
	 * Keys used in the method return array
	 */
	const ENTITYID_KEY = 'entityId';
	const MATCHEDTERM_KEY = 'matchedTerm';
	const MATCHEDTERMTYPE_KEY = 'matchedTermType';
	const DISPLAYTERMS_KEY = 'displayTerms';

	/**
	 * @since 0.5
	 *
	 * @param string $text Term text to search for
	 * @param string $languageCode Language code to search in
	 * @param string $entityType Type of Entity to return
	 * @param string[] $termTypes Types of Term to return, array of Wikibase\TermIndexEntry::TYPE_*
	 *
	 * @returns array[] array of arrays containing the following:
	 *          [ENTITYID_KEY] => EntityId EntityId object
	 *          [MATCHEDTERM_KEY] => Term matched Term object
	 *          [MATCHEDTERMTYPE_KEY] => string one of Wikibase\TermIndexEntry::TYPE_*
	 *          [DISPLAYTERMS_KEY] => array array with possible keys Wikibase\TermIndexEntry::TYPE_*
	 *                                   Wikibase\TermIndexEntry::TYPE_LABEL => Term
	 *                                   Wikibase\TermIndexEntry::TYPE_DESCRIPTION => Term
	 */
	public function searchForEntities( $text, $languageCode, $entityType, array $termTypes );

}
