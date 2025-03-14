<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use Wikibase\Repo\View\ScopedTypeaheadCodexModule;

/**
 * This is a hook handler interface, see docs/Hooks.md in MediaWiki core.
 * Use the hook name "WikibaseRepoSearchableEntityScopesMessages" to register
 * handlers implementing this interface.
 *
 * @license GPL-2.0-or-later
 */
interface WikibaseRepoSearchableEntityScopesMessagesHook {

	/**
	 * Hook handler method for the 'WikibaseRepoSearchableEntityScopesMessages' hook
	 * @param array &$messages A dictionary mapping entity types (`lexeme`, `property`)
	 *              to the message key of the translation for the entity name.
	 * @see ScopedTypeaheadCodexModule
	 */
	public function onWikibaseRepoSearchableEntityScopesMessages( array &$messages ): void;

}
