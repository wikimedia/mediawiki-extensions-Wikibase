<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

/**
 * This is a hook handler interface, see docs/Hooks.md in MediaWiki core.
 * Use the hook name "WikibaseRepoDataTypes" to register
 * handlers implementing this interface.
 *
 * @license GPL-2.0-or-later
 */
interface WikibaseRepoDataTypesHook {

	/**
	 * Called when constructing the top-level
	 * {@link \Wikibase\Repo\WikibaseRepo WikibaseRepo} factory
	 * May be used to define additional data types.
	 * See also
	 * {@link \Wikibase\Client\Hooks\WikibaseClientDataTypesHook WikibaseClientDataTypesHook}.
	 *
	 * Hook handlers may add additional definitions.
	 * See {@link https://doc.wikimedia.org/Wikibase/master/php/docs_topics_datatypes.html datatypes documentation}
	 * for details.
	 *
	 * @param array[] &$dataTypeDefinitions the array of data type definitions,
	 * as defined by WikibaseRepo.datatypes.php.
	 */
	public function onWikibaseRepoDataTypes( array &$dataTypeDefinitions ): void;

}
