<?php

namespace Wikibase;
use Parser, ParserOutput;
use \Wikibase\LocalItemsTable as LocalItemsTable;
use \Wikibase\Settings as Settings;
use \Wikibase\Sites as Sites;

/**
 * Handles language links.
 * TODO: do we really want to refresh this on re-render? push updates from the repo seem to make more sense
 *
 * @since 0.1
 *
 * @file LangLinkHandler.php
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Nikola Smolenski <smolensk@eunet.rs>
 */
class LangLinkHandler {

        /**
         * Fetches a links from the local items table
         *
         * @since 0.1
         *
         * @param Parser $parser
         * @return array a list of SiteLink objects|false
         */
	public static function getLocalItemLinks( Parser $parser ) {
                $parserOutput = $parser->getOutput();

                $localItem = LocalItemsTable::singleton()->selectRow( null, array( 'page_title' => $parser->getTitle()->getDBkey() ) );

                if ( $localItem !== false ) {
                        /**
                         * @var LocalItem $localItem
                         * @var SiteLink $link
                         */
			return $localItem->getItem()->getSiteLinks();

                }

		return false;
	}

	/**
	 * Shall a page have interwiki links in the sidebar?
	 *
	 * @since 0.1
	 *
	 * @param Parser $parser
	 * @return true|false
	 */
	public static function doInterwikiLinks( Parser $parser ) {
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
	public static function useRepoLinks( Parser $parser ) {
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
	public static function suppressRepoLinks( Parser $parser, &$repoLinks ) {
		$out = $parser->getOutput();
		$nei = self::getNoExternalInterlang( $parser->getOutput() );

		if( array_key_exists( '*', $nei ) ) {
			$repoLinks = array();
		} else if ( is_array( $repoLinks ) && is_array( $nei ) ) {
			$siteLinksRemove = array();

			// TODO: hackish until we have a way of knowing site group
			$sitesuffix = 'wiki';

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
	public static function getNoExternalInterlang( ParserOutput $out ) {
		$nei = $out->getProperty( 'no_external_interlang' );

		if( empty( $nei ) ) {
			$nei = array();
		} else {
			$nei = unserialize( $nei );
		}

		return $nei;
	}

}
