<?php

namespace Wikibase;

use FormatJson;
use MediaWikiSite;
use ResourceLoaderContext;
use ResourceLoaderModule;
use Wikibase\Client\WikibaseClient;

/**
 * Provides information about the current (client) site
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
class SiteModule extends ResourceLoaderModule {

	/**
	 * Used to propagate information about the current site to JavaScript.
	 * Sites infos will be available in 'wbCurrentSite' config var.
	 * @see ResourceLoaderModule::getScript
	 *
	 * @param ResourceLoaderContext $context
	 *
	 * @return string
	 */
	public function getScript( ResourceLoaderContext $context ) {
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		/**
		 * @var MediaWikiSite $site
		 */
		$site = $wikibaseClient->getSite();

		$currentSite = array();
		if ( $site ) {
			$currentSite = array(
				'globalSiteId' => $site->getGlobalId(),
				'languageCode' => $site->getLanguageCode(),
				'langLinkSiteGroup' => $wikibaseClient->getLangLinkSiteGroup()
			);
		}

		return 'mediaWiki.config.set( "wbCurrentSite", ' . FormatJson::encode( $currentSite ) . ' );';
	}

}
