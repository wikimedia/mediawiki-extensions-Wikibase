<?php

namespace Wikibase\Client\Hooks;

/**
 * @license GPL-2.0-or-later
 */
interface WikibaseHandleChangesHook {

	/**
	 * Hook runner for the 'WikibaseHandleChanges' hook
	 *
	 * @param array $changes
	 * @param array $rootJobParams
	 * @return bool
	 */
	public function onWikibaseHandleChanges( array $changes, array $rootJobParams = [] );

}
