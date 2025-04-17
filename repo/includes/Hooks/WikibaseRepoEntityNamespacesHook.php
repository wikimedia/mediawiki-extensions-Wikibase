<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

/**
 * This is a hook handler interface, see docs/Hooks.md in MediaWiki core.
 * Use the hook name "WikibaseRepoEntityNamespaces" to register
 * handlers implementing this interface.
 *
 * @license GPL-2.0-or-later
 */
interface WikibaseRepoEntityNamespacesHook {

	/**
	 * Called in the default 'entitySources' setting to allow additional mappings
	 * between Entity types and namespace IDs to be defined.
	 *
	 * Only used if no custom entity sources are defined.
	 *
	 * @param array &$entityNamespaces An associative array mapping Entity types to namespace ids.
	 */
	public function onWikibaseRepoEntityNamespaces( array &$entityNamespaces ): void;

}
