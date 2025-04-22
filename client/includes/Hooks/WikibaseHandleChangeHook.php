<?php

namespace Wikibase\Client\Hooks;

use Wikibase\Lib\Changes\EntityChange;

/**
 * This is a hook handler interface, see docs/Hooks.md in MediaWiki core.
 * Use the hook name "WikibaseHandleChange" to register
 * handlers implementing this interface.
 *
 * @license GPL-2.0-or-later
 */
interface WikibaseHandleChangeHook {

	/**
	 * Called by {@link \Wikibase\Client\Changes\ChangeHandler::handleChanges() ChangeHandler::handleChanges()}
	 * to allow alternative processing of changes.
	 *
	 * @param EntityChange $change
	 * @param array $rootJobParams Any relevant root job parameters to be inherited by child jobs.
	 * @return bool|void
	 */
	public function onWikibaseHandleChange( EntityChange $change, array $rootJobParams = [] );

}
