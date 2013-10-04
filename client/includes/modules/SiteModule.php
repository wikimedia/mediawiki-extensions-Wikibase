<?php

namespace Wikibase;

use ResourceLoaderModule, ResourceLoaderContext, MediaWikiSite, Site, Sites;

/**
 * Provides information about the current (client) site
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class SiteModule extends ResourceLoaderModule {

	/**
	 * Used to propagate information about the current site to JavaScript.
	 * Sites infos will be available in 'wbCurrentSite' config var.
	 * @see ResourceLoaderModule::getScript
	 *
	 * @since 0.4
	 *
	 * @param ResourceLoaderContext $context
	 *
	 * @return string
	 */
	public function getScript( ResourceLoaderContext $context ) {
		/**
		 * @var MediaWikiSite $site
		 */
		$site = Sites::singleton()->getSite( Settings::get( 'siteGlobalID' ) );

		$currentSite = array();
		if ( $site ) {
			$languageName = Utils::fetchLanguageName( $site->getLanguageCode() );
			$currentSite = array(
				'shortName' => $languageName,
				'name' => $languageName,
				'globalSiteId' => $site->getGlobalId(),
				'languageCode' => $site->getLanguageCode()
			);
		}

		return 'mediaWiki.config.set( "wbCurrentSite", ' . \FormatJson::encode( $currentSite ) . ' );';
	}
}
