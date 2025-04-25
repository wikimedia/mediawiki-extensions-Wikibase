<?php

declare( strict_types = 1 );

namespace Wikibase\DataAccess\Hooks;

use MediaWiki\Title\Title;

/**
 * This is a hook handler interface, see docs/Hooks.md in MediaWiki core.
 * Use the hook name "GetEntityContentModelForTitle" to register
 * handlers implementing this interface.
 *
 * @license GPL-2.0-or-later
 */
interface GetEntityContentModelForTitleHook {

	/**
	 * Called to determine what the entity content model of the Title is.
	 * Extensions can override it so entity content model does not equal page content model.
	 *
	 * @param Title $title Title object for the page.
	 * @param string &$contentModel Content model for the page. Extensions can override this.
	 */
	public function onGetEntityContentModelForTitle( Title $title, string &$contentModel ): void;

}
