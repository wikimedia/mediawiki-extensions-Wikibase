<?php

namespace Wikibase\Client\Hooks;

use Wikibase\Lib\Changes\EntityChange;

/**
 * @license GPL-2.0-or-later
 */
interface WikibaseHandleChangeHook {

	/**
	 * Hook runner for the 'WikibaseHandleChange' hook
	 *
	 * @param EntityChange $change
	 * @param array $rootJobParams
	 * @return bool
	 */
	public function onWikibaseHandleChange( $change, array $rootJobParams = [] );

}
