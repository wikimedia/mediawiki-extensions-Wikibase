<?php declare( strict_types = 1 );

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
	public function searchForEntities( string $text, string $languageCode, string $entityType, array $termTypes ): array;

}
