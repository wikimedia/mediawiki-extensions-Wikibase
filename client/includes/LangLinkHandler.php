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
	 * @param Parser $parser
	 * @return array of SiteLink
	 */
	public static function getLocalItemLinks( \Parser $parser ) {
		$linkTable = SiteLinkCache::singleton();

		$siteid = Settings::get( 'siteGlobalID' );

		// TODO: obtain global id
		$itemId = $linkTable->getItemIdForPage( $siteid, $parser->getTitle()->getFullText() );

		if ( $itemId !== false ) {
			$item = EntityCache::singleton()->getItem( $itemId );

			if ( $item !== false ) {
				return $item->getSiteLinks();
			}
		}

		return array();
	}

	/**
	 * Shall a page have interwiki links in the sidebar?
	 *
	 * @since 0.1
	 *
	 * @param Parser $parser
	 * @return boolean
	 */
	public static function doInterwikiLinks( \Parser $parser ) {
		if ( $parser->getOptions()->getInterfaceMessage() ) {
			return false;
		}
		return true;
	}

	/**
	 * Shall a page have interwiki links from Wikidata repo?
	 *
	 * @since 0.1
	 *
	 * @param Parser $parser
	 * @return boolean
	 */
	public static function useRepoLinks( \Parser $parser ) {
		$title = $parser->getTitle();
		if( !in_array( $title->getNamespace(), Settings::get( 'namespaces' ) ) ) {
			return false;
		}

		return true;
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
		$nei = self::getNoExternalInterlang( $parser->getOutput() );

		if( array_key_exists( '*', $nei ) ) {
			$repoLinks = array();
		} else if ( is_array( $repoLinks ) && is_array( $nei ) ) {
			$siteLinksRemove = array();

			$siteid = Settings::get( 'globalSiteID' );

			// Remove the links specified by noexternalinterlang parser function.
			foreach( array_keys( $nei ) as $code ) {
				array_push( $siteLinksRemove, SiteLink::newFromText( $code . $sitesuffix, $parser->getTitle()->mDbkeyform ) );
			}

			$repoLinks = array_diff( $repoLinks, $siteLinksRemove );
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
