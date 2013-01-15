<?php

namespace Wikibase;
use ResourceLoaderModule, ResourceLoaderContext, MediaWikiSite, Site, Sites;

/**
 *
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
 * @since 0.2
 * @todo This modules content should be invalidated whenever sites stuff (config) changes
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
class SitesModule extends ResourceLoaderModule {

	/**
	 * Used to propagate information about sites to JavaScript.
	 * Sites infos will be available in 'wbSiteDetails' config var.
	 * @see ResourceLoaderModule::getScript
	 *
	 * @since 0.2
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
		foreach ( \SitesTable::newInstance()->getSites() as $site ) {
			if ( $site->getType() === Site::TYPE_MEDIAWIKI && $site->getGroup() === 'wikipedia' ) {
				$languageName = Utils::fetchLanguageName( $site->getLanguageCode() );

				$sites[$site->getLanguageCode()] = array(
					'shortName' => $languageName,
					'name' => $languageName,
					'globalSiteId' => $site->getGlobalId(),
					'pageUrl' => $site->getPageUrl(),
					'apiUrl' => $site->getFileUrl( 'api.php' ),
					'languageCode' => $site->getLanguageCode()
				);
			}
		}

		return 'mediaWiki.config.set( "wbSiteDetails", ' . \FormatJson::encode( $sites ) . ' );';
	}
}