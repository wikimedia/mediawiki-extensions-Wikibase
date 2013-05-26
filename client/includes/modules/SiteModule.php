<?php

namespace Wikibase;
use ResourceLoaderModule, ResourceLoaderContext, MediaWikiSite, Site, Sites;

/**
 * Provides information about the current (client) site
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseClient
 *
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
		$sites = array();

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
