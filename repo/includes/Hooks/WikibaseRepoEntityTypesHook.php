<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

/**
 * This is a hook handler interface, see docs/Hooks.md in MediaWiki core.
 * Use the hook name "WikibaseRepoEntityTypes" to register
 * handlers implementing this interface.
 *
 * @license GPL-2.0-or-later
 */
interface WikibaseRepoEntityTypesHook {

	/**
	 * Called when constructing the top-level
	 * {@link \Wikibase\Repo\WikibaseRepo WikibaseRepo} factory.
	 * May be used to define additional entity types.
	 * See also
	 * {@link \Wikibase\Client\Hooks\WikibaseClientDataTypesHook WikibaseClientDataTypesHook}.
	 *
	 * Hook handlers may add additional definitions.
	 * See {@link https://doc.wikimedia.org/Wikibase/master/php/docs_topics_entitytypes.html entitytypes documentation}
	 * for details.
	 *
	 * This hook runs during early initialization; its handlers must obey the
	 * {@link \MediaWiki\Hook\MediaWikiServicesHook::onMediaWikiServices() MediaWikiServicesHook rules},
	 * i.e. not declare any service dependencies nor access any unsafe services dynamically.
	 *
	 * @param array[] &$entityTypeDefinitions The array of entity type definitions,
	 * as defined by WikibaseLib.entitytypes.php.
	 */
	public function onWikibaseRepoEntityTypes( array &$entityTypeDefinitions ): void;

}
