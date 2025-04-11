<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use Wikibase\Lib\Store\EntityByLinkedTitleLookup;

/**
 * This is a hook handler interface, see docs/Hooks.md in MediaWiki core.
 * Use the hook name "GetEntityByLinkedTitleLookup" to register
 * handlers implementing this interface.
 *
 * @license GPL-2.0-or-later
 */
interface GetEntityByLinkedTitleLookupHook {

	/**
	 * Allows extensions to add custom EntityByLinkedTitleLookup services.
	 *
	 * @param EntityByLinkedTitleLookup &$lookup
	 */
	public function onGetEntityByLinkedTitleLookup( EntityByLinkedTitleLookup &$lookup ): void;

}
