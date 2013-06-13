<?php

namespace Wikibase;
use ResourceLoaderModule, ResourceLoaderContext, MediaWikiSite, Site;

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

		$groups = Settings::get( "siteLinkGroups" );

		/**
		 * @var MediaWikiSite $site
		 */
		foreach ( \SiteSQLStore::newInstance()->getSites() as $site ) {
			$group = $site->getGroup();

			if ( $site->getType() === Site::TYPE_MEDIAWIKI && in_array( $group, $groups ) ) {
				$languageName = Utils::fetchLanguageName( $site->getLanguageCode() );

				// Use protocol relative URIs, as it's safe to assume that all wikis support the same protocol
				list( $pageUrl, $apiUrl ) = preg_replace(
					"/^https?:/i",
					'',
					array(
						$site->getPageUrl(),
						$site->getFileUrl( 'api.php' )
					)
				);

				//TODO: figure out which name ist best
				//$localIds = $site->getLocalIds();
				//$name = empty( $localIds['equivalent'] ) ? $site->getGlobalId() : $localIds['equivalent'][0];

				$sites[$site->getLanguageCode()] = array(
					'shortName' => $languageName,
					'name' => $languageName, // use short name for both, for now
					'globalSiteId' => $site->getGlobalId(),
					'pageUrl' => $pageUrl,
					'apiUrl' => $apiUrl,
					'languageCode' => $site->getLanguageCode(),
					'group' => $group
				);
			}
		}

		return 'mediaWiki.config.set( "wbSiteDetails", ' . \FormatJson::encode( $sites ) . ' );';
	}
}
