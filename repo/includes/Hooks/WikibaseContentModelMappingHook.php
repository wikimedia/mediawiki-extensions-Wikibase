<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

/**
 * This is a hook handler interface, see docs/Hooks.md in MediaWiki core.
 * Use the hook name "WikibaseContentModelMapping" to register
 * handlers implementing this interface.
 *
 * @license GPL-2.0-or-later
 */
interface WikibaseContentModelMappingHook {

	/**
	 * Called by {@link \Wikibase\Repo\WikibaseRepo::getContentModelMappings() WikibaseRepo::getContentModelMappings()}
	 * to allow additional mappings between Entity types and content model identifiers to be defined.
	 *
	 * This hook runs during early initialization; its handlers must obey the
	 * {@link \MediaWiki\Hook\MediaWikiServicesHook::onMediaWikiServices() MediaWikiServicesHook rules},
	 * i.e. not declare any service dependencies nor access any unsafe services dynamically.
	 *
	 * @param string[] &$map An associative array mapping Entity types to content model ids.
	 */
	public function onWikibaseContentModelMapping( array &$map ): void;

}
