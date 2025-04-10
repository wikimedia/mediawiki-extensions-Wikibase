<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use Wikibase\Repo\Content\EntityContent;

/**
 * This is a hook handler interface, see docs/Hooks.md in MediaWiki core.
 * Use the hook name "WikibaseTextForSearchIndex" to register
 * handlers implementing this interface.
 *
 * @license GPL-2.0-or-later
 */
interface WikibaseTextForSearchIndexHook {

	/**
	 * Called by {@link EntityContent::getTextForSearchIndex()} to allow extra text to be passed to the search engine for indexing.
	 * If the hook function returns false, no text at all will be passed to the search index.
	 *
	 * @param EntityContent $entityContent EntityContent to be indexed.
	 * @param string &$text The text to pass to the index (to be modified).
	 * @return bool|void
	 */
	public function onWikibaseTextForSearchIndex( EntityContent $entityContent, string &$text );

}
