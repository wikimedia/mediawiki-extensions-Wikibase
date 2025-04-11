<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

/**
 * This is a hook handler interface, see docs/Hooks.md in MediaWiki core.
 * Use the hook name "WikibaseClientDataTypes" to register
 * handlers implementing this interface.
 *
 * @license GPL-2.0-or-later
 */
interface WikibaseClientDataTypesHook {

	/**
	 * Called when constructing the top-level WikibaseClient factory
	 * May be used to define additional data types
	 * See also the Wikibase::Repo::Hooks::WikibaseRepoDataTypesHook.
	 *
	 * Hook handlers may add additional definitions.
	 * See the datatypes documentation for details.
	 *
	 * @param array &$dataTypeDefinitions The array of data type definitions, as defined
	 *        by WikibaseClient.datatypes.php
	 */
	public function onWikibaseClientDataTypes( array &$dataTypeDefinitions ): void;

}
