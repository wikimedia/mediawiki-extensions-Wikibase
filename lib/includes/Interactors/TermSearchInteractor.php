<?php

namespace Wikibase\Lib\Interactors;

/**
 * Interface for searching for terms
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
interface TermSearchInteractor {

	/**
	 * @param string $text Term text to search for
	 * @param string $languageCode Language code to search in
	 * @param string $entityType Type of Entity to return
	 * @param string[] $termTypes Types of Term to return, array of Wikibase\Lib\TermIndexEntry::TYPE_*
	 *
	 * @return TermSearchResult[]
	 */
	public function searchForEntities( $text, $languageCode, $entityType, array $termTypes );

}
