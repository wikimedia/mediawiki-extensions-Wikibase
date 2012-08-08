<?php

namespace Wikibase;
use Http, Parser, ParserOutput;
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
 * @licence	GNU GPL v2+
 * @author	Nikola Smolenski <smolensk@eunet.rs>
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
	 * @return true|false
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

	/**
	 * Get the list of links for a title.
	 * @return Array of links, empty array for no links, false for failure.
	 */
	protected static function getLinks( $title_text ) {
		$source = Settings::get( 'source' );
		if( isset( $source['api'] ) ) {
			return self::getLinksFromApi( $title_text, $source['api'] );
		} elseif( isset( $source['var'] ) ) {
			return self::getLinksFromVar( $title_text, $source['var'] );
		} elseif( isset( $source['dir'] ) ) {
			return self::getLinksFromFile( $title_text, $source['dir'] );
		} else {
			return array();
		}
	}

	/**
	 * Get the list of links for a title from an API.
	 * @return Array of links, empty array for no links, false for failure.
	 */
	protected static function getLinksFromApi( $title_text, $api ) {
		global $wgLanguageCode;
		//TODO: Read from configuration.
		$siteSuffix = "wiki";

		$url = $api .
			"?action=wbgetitems&format=php&sites=" . $wgLanguageCode . $siteSuffix . "&titles=" . urlencode( $title_text );
		$api_response = Http::get( $url );
		$api_response = unserialize( $api_response );

		if( !is_array( $api_response ) || isset( $api_response['error'] ) ) {
			return false;
		}

		// Repack the links
		$item = reset( $api_response['items'] );
		$links = array();

		if ( isset( $item['sitelinks'] ) ) {
			$sitelinks = $item['sitelinks'];
			foreach( $sitelinks as $sitelink ) {
				$site = preg_replace( "/$siteSuffix$/", "", $sitelink['site'] );
				$links[$site] = array( 'site' => $site, 'title' => $sitelink['title'] );
			}
		}

		return $links;
	}

	/**
	 * Get the list of links for a title from a variable. This would generally be used for testing.
	 * @return Array of links, empty array for no links, false for failure.
	 */
	protected static function getLinksFromVar( $title_text, $var ) {
		return isset( $var[$title_text] )? $var[$title_text]: false;
	}

	/**
	 * Get the list of links for a title from a file. This would generally be used for testing.
	 * @return Array of links, empty array for no links, false for failure.
	 */
	protected static function getLinksFromFile( $title_text, $dir ) {
		$file = "$dir/$title_text.json";
		if( file_exists( $file ) ) {
			return get_object_vars( json_decode( file_get_contents( $file ) ) );
		} else {
			return false;
		}
	}

	/**
	 * Read interlanguage links from a database, and return them in the same format as getLinks()
	 *
	 * @param	$dbr DatabaseBase
	 * @param	$articleid int ID of the article whose links should be returned.
	 * @return	array The array with the links. If there are no links, an empty array is returned.
	 * @version	Copied from InterlanguageExtension rev 114818
	 */
	protected static function readLinksFromDB( $dbr, $articleid ) {
		$res = $dbr->select(
			array( 'langlinks' ),
			array( 'll_lang', 'll_title' ),
			array( 'll_from' => $articleid ),
			__METHOD__
		);
		$a = array();
		foreach( $res as $row ) {
			$a[$row->ll_lang] = $row->ll_title;
		}
		return $a;
	}

}
