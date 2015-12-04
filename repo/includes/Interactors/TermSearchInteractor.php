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
	 * @since 0.5
	 *
	 * @param string $text Term text to search for
	 * @param string $languageCode Language code to search in
	 * @param string $entityType Type of Entity to return
	 * @param string[] $termTypes Types of Term to return, array of Wikibase\TermIndexEntry::TYPE_*
	 *
	 * @returns TermSearchResult[]
	 */
	public function searchForEntities( $text, $languageCode, $entityType, array $termTypes );

}
