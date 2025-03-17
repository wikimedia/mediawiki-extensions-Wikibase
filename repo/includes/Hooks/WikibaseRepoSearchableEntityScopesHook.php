<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

/**
 * This is a hook handler interface, see docs/Hooks.md in MediaWiki core.
 * Use the hook name "WikibaseRepoSearchableEntityScopes" to register
 * handlers implementing this interface.
 *
 * @license GPL-2.0-or-later
 */
interface WikibaseRepoSearchableEntityScopesHook {

	/**
	 * Hook handler method for the 'WikibaseRepoSearchableEntityScopes' hook
	 * @param array &$searchableEntityScopes A dictionary mapping entity types (`lexeme`,
	 * 		`property`) to the namespaceId of the associated entity namespace
	 */
	public function onWikibaseRepoSearchableEntityScopes( array &$searchableEntityScopes ): void;

}
