<?php

namespace Wikibase\Client\Hooks;

/**
 * @license GPL-2.0-or-later
 */
interface WikibaseHandleChangeHook {

	/**
	 * Hook runner for the 'WikibaseHandleChange' hook
	 *
	 * @param $change
	 * @param array $rootJobParams
	 * @return bool
	 */
	public function onWikibaseHandleChange( $change, array $rootJobParams = [] );

}
