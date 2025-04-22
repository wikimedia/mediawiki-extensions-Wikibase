<?php

namespace Wikibase\Client\Hooks;

use Wikibase\Lib\Changes\EntityChange;

/**
 * This is a hook handler interface, see docs/Hooks.md in MediaWiki core.
 * Use the hook name "WikibaseHandleChanges" to register
 * handlers implementing this interface.
 *
 * @license GPL-2.0-or-later
 */
interface WikibaseHandleChangesHook {

	/**
	 * Called by {@link \Wikibase\Client\Changes\ChangeHandler::handleChanges() ChangeHandler::handleChanges()}
	 * to allow pre-processing of changes.
	 *
	 * @param EntityChange[] $changes A list of Change objects.
	 * @param array $rootJobParams Any relevant root job parameters to be inherited by child jobs.
	 * @return bool|void
	 */
	public function onWikibaseHandleChanges( array $changes, array $rootJobParams = [] );

}
