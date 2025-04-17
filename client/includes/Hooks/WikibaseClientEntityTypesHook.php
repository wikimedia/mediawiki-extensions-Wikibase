<?php

namespace Wikibase\Client\Hooks;

/**
 * This is a hook handler interface, see docs/Hooks.md in MediaWiki core.
 * Use the hook name "WikibaseClientEntityTypes" to register
 * handlers implementing this interface.
 *
 * @license GPL-2.0-or-later
 */
interface WikibaseClientEntityTypesHook {

	/**
	 * Called when constructing the top-level
	 * {@link \Wikibase\Client\WikibaseClient WikibaseClient} factory
	 * May be used to define additional entity types.
	 * See also the {@link \Wikibase\Repo\Hooks\WikibaseRepoEntityTypesHook WikibaseRepoEntityTypesHook}.
	 *
	 * Hook handlers may add additional definitions.
	 * See entitytypes documentation for details.
	 *
	 * @param array &$entityTypeDefinitions the array of entity type definitions, as defined
	 *              by WikibaseLib.entitytypes.php
	 */
	public function onWikibaseClientEntityTypes( array &$entityTypeDefinitions ): void;

}
