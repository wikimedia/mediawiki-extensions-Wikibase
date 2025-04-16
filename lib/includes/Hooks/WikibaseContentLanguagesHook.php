<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Hooks;

use Wikibase\Lib\ContentLanguages;

/**
 * This is a hook handler interface, see docs/Hooks.md in MediaWiki core.
 * Use the hook name "WikibaseContentLanguages" to register
 * handlers implementing this interface.
 *
 * @license GPL-2.0-or-later
 */
interface WikibaseContentLanguagesHook {

	/**
	 * This hook is called to define the content languages per context.
	 *
	 * @param ContentLanguages[] &$contentLanguages An associative array mapping contexts
	 * ('term', 'monolingualtext', extension-specific…) to ContentLanguages objects.
	 */
	public function onWikibaseContentLanguages( array &$contentLanguages ): void;

}
