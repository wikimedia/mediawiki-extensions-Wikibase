<?php

namespace Wikibase;

/**
 * Handles language links.
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
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseClient
 * @ingroup RefuctoredCode
 *
 * @licence GNU GPL v2+
 * @author Nikola Smolenski <smolensk@eunet.rs>
 */
class LangLinkHandler {

	/**
	 * Finds the corresponding item and fetches its links from the entity cache.
	 *
	 * @since 0.1
	 *
	 * @param \Parser $parser
	 * @return array of SiteLink
	 */
	public static function getEntityCacheLinks( \Parser $parser ) {
		$itemId = ClientStoreFactory::getStore()->newSiteLinkCache()->getItemIdForLink(
			Settings::get( 'siteGlobalID' ),
			$parser->getTitle()->getFullText()
		);

		if ( $itemId !== false ) {
			$item = ClientStoreFactory::getStore()->newEntityCache()->getItem( $itemId );

			if ( $item !== false ) {
				return $item->getSiteLinks();
			}
		}

		return array();
	}

	/**
	 * Checks if a page have interwiki links from Wikidata repo?
	 * Disabled for a page when either:
	 * - Wikidata not enabled for namespace
	 * - nel parser function = * (suppress all repo links)
	 * 
	 * @since 0.1
	 *
	 * @param \Parser $parser
	 * @return boolean
	 */
	public static function useRepoLinks( \Parser $parser ) {
		$title = $parser->getTitle();

		// use repoLinks in only the namespaces specified in settings
		if ( in_array( $title->getNamespace(), Settings::get( 'namespaces' ) ) ) {
                	$nel = self::getNoExternalLangLinks( $parser->getOutput() );

                	// unsets all the repolinks
                	if( array_key_exists( '*', $nel ) ) {
				return false;
			}

			return true;
		}

		return false;
	}

	/**
	 * Suppress specific repo interwiki links
	 *
	 * @since 0.1
	 *
	 * @param \Parser $parser
	 * @param array $repoLinks - array of \Wikibase\SiteLink objects
	 *
	 * @return true
	 */
	public static function suppressRepoLinks( \Parser $parser, &$repoLinks ) {
		$out = $parser->getOutput();
		$nel = self::getNoExternalLangLinks( $out );

		// unset only specified repolinks
		if ( is_array( $repoLinks ) && is_array( $nel ) ) {

			// Remove the links specified by noexternallanglinks parser function.
			foreach ( array_keys( $nel ) as $code ) {
				foreach ( $repoLinks as $key => &$repoLink ) {
					// site corresponding to the $nel code specified and site group
					$site = \SitesTable::singleton()->selectRow( null, array(
						'language' => $code,
						'group' => Settings::get( 'siteGroup' )
					) );

					// check if site is found or not
					if ( $site !== false ) {
						$nelGlobalId = $site->getGlobalId();

						// global id for the repo link
						$repoLinkGlobalId = $repoLink->getSite()->getGlobalId();

						if ( $repoLinkGlobalId == $nelGlobalId ) {
							unset( $repoLinks[$key] );
						}
					}
				}
			}
		}
		return true;
	}

	/**
	 * Get no_external_interlang parser property.
	 *
	 * @param \ParserOutput
	 *
	 * @return Array Empty array if not set.
	 */
	public static function getNoExternalLangLinks( \ParserOutput $out ) {
		$nel = $out->getProperty( 'noexternallanglinks' );

		if( empty( $nel ) ) {
			$nel = array();
		} else {
			$nel = unserialize( $nel );
		}

		return $nel;
	}

}
