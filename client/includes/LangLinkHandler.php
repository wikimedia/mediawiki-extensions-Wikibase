<?php

namespace Wikibase;

/**
 * Handles language links.
 *
 * @since 0.1
 *
 * @file LangLinkHandler.php
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
		$linkTable = SiteLinkCache::singleton();

		$itemId = $linkTable->getItemIdForPage( Settings::get( 'siteGlobalID' ), $parser->getTitle()->getFullText() );

		if ( $itemId !== false ) {
			$item = EntityCache::singleton()->getItem( $itemId );

			if ( $item !== false ) {
				return $item->getSiteLinks();
			}
		}

		return array();
	}

	/**
	 * Shall a page have interwiki links from Wikidata repo?
	 * Disabled for a page when either:
	 * - Wikidata not enabled for namespace
	 * - nei parser function = * (suppress all repo links) 
	 * 
	 * @since 0.1
	 *
	 * @param Parser $parser
	 * @return boolean
	 */
	public static function useRepoLinks( \Parser $parser ) {
		$title = $parser->getTitle();

		// use repoLinks in only the namespaces specified in settings
		if ( in_array( $title->getNamespace(), Settings::get( 'namespaces' ) ) ) {
                	$nei = self::getNoExternalInterlang( $parser->getOutput() );

                	// unsets all the repolinks
                	if( array_key_exists( '*', $nei ) ) {
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
	 * @param Parser $parser
	 * @param array $repoLinks - array of \Wikibase\SiteLink objects
	 *
	 * @return true
	 */
	public static function suppressRepoLinks( \Parser $parser, &$repoLinks ) {
		$out = $parser->getOutput();
		$nei = self::getNoExternalInterlang( $out );

		// unset only specified repolinks
		if ( is_array( $repoLinks ) && is_array( $nei ) ) {
			// TODO: hackish until we have a way of knowing site group
			$globalId = Settings::get( 'siteGlobalID' );

			// Remove the links specified by noexternalinterlang parser function.
			foreach( array_keys( $nei ) as $code ) {
				$i = 0;
				foreach( $repoLinks as $repoLink ) {
					if ( $repoLink->getSiteID() == $code . Settings::get( 'siteGroup' ) ) {
						unset( $repoLinks[$i] );
					}
					$i++;
				}
			}

		}

		return true;
	}

	/**
	 * Get no_external_interlang parser property.
	 *
	 * @return Array Empty array if not set.
	 */
	public static function getNoExternalInterlang( \ParserOutput $out ) {
		$nei = $out->getProperty( 'no_external_interlang' );

		if( empty( $nei ) ) {
			$nei = array();
		} else {
			$nei = unserialize( $nei );
		}

		return $nei;
	}

}
