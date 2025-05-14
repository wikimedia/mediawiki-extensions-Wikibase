<?php

declare( strict_types = 1 );

namespace Wikibase\Client;

use MediaWiki\MediaWikiServices;

/**
 * @license GPL-2.0-or-later
 */
class LinkItemModuleMethods {

	/**
	 * Used to propagate configuration for the linkitem feature to JavaScript.
	 * This is used in the "wikibase.client.linkitem.init" module.
	 */
	public static function getLinkitemConfiguration(): array {
		$cache = MediaWikiServices::getInstance()->getLocalServerObjectCache();
		$key = $cache->makeKey(
			'wikibase-client',
			'siteConfiguration'
		);
		return $cache->getWithSetCallback(
			$key,
			$cache::TTL_DAY, // when changing the TTL, also update linkItemTags in options.md
			function () {
				$site = WikibaseClient::getSite();
				$currentSite = [
					'globalSiteId' => $site->getGlobalId(),
					'languageCode' => $site->getLanguageCode(),
					'langLinkSiteGroup' => WikibaseClient::getLangLinkSiteGroup(),
				];
				$value = [ 'currentSite' => $currentSite ];

				$tags = WikibaseClient::getSettings()->getSetting( 'linkItemTags' );
				if ( $tags !== [] ) {
					$value['tags'] = $tags;
				}

				return $value;
			}
		);
	}

}
