<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use CirrusSearch\Hooks\CirrusSearchAddQueryFeaturesHook;
use CirrusSearch\Query\SimpleKeywordFeature;
use CirrusSearch\SearchConfig;
use Wikibase\Client\MoreLikeWikibase;

/**
 * Handler for the CirrusSearchAddQueryFeatures hook.
 * This needs to be a separate handler as this class depends on the CirrusSearch extension.
 *
 * @license GPL-2.0-or-later
 */
class CirrusSearchAddQueryFeaturesHookHandler implements CirrusSearchAddQueryFeaturesHook {

	/**
	 * This hook is called to register new search keywords
	 * @param SearchConfig $config
	 * @param SimpleKeywordFeature[] &$extraFeatures
	 */
	public function onCirrusSearchAddQueryFeatures( SearchConfig $config, array &$extraFeatures ): void {
		$extraFeatures[] = new MoreLikeWikibase( $config );
	}

}
