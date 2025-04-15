<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

/**
 * This is a hook handler interface, see docs/Hooks.md in MediaWiki core.
 * Use the hook name "WikibaseRepoEntitySearchHelperCallbacks" to register
 * handlers implementing this interface.
 *
 * @license GPL-2.0-or-later
 */
interface WikibaseRepoEntitySearchHelperCallbacksHook {

	/**
	 * Define or modify entity search helper callbacks.
	 *
	 * This hook is called when the entity search is initialized.
	 * $callbacks is an associative array from entity type to callback:
	 * each callback takes a {@link \MediaWiki\Request\WebRequest WebRequest} parameter
	 * and returns an {@link \Wikibase\Repo\Api\EntitySearchHelper EntitySearchHelper}.
	 * Usually, these callbacks are defined via the entity type definitions,
	 * using the {@link \Wikibase\Lib\EntityTypeDefinitions::ENTITY_SEARCH_CALLBACK ENTITY_SEARCH_CALLBACK} key;
	 * however, it is possible to change the callbacks via this hook,
	 * or even to install additional searchable entity types that are not registered as entity types.
	 *
	 * @param callable[] &$callbacks
	 */
	public function onWikibaseRepoEntitySearchHelperCallbacks( array &$callbacks ): void;

}
