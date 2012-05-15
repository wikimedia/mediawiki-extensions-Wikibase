<?php

/**
 * Utility functions for Wikibase.
 *
 * @since 0.1
 *
 * @file WikibaseUtils.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class WikibaseUtils {

	/**
	 * Returns a list of language codes that Wikibase supports,
	 * ie the languages that a label or deswcription can be in.
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public static function getLanguageCodes() {
		static $languageCodes = null;

		if ( is_null( $languageCodes ) ) {
			$languageCodes = array_keys( Language::fetchLanguageNames() );
		}

		return $languageCodes;
	}

	/**
	 * Returns the identifiers of the sites supported by the Wikibase instance.
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public static function getSiteIdentifiers() {
		static $ids = false;

		if ( $ids === false ) {
			$ids = array_keys( WBSettings::get( 'siteIdentifiers' ) );
		}

		return $ids;
	}

	/**
	 * Returns the index of the identifiers for the sites supported by the Wikibase instance.
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public static function getIndexSites() {
		static $idx = false;

		if ( $idx === false ) {
			$num = 0;
			$idx = array();
			foreach (self::getSiteIdentifiers() as $key) {
				$idx[$key] = $num++;
			}
		}

		return $idx;
	}

	/**
	 * Returns the full url for the specified site.
	 * A page can also be provided, which is then added to the url.
	 *
	 * @since 0.1
	 *
	 * @param string $siteId
	 * @param string $pageTitle
	 *
	 * @return string|false
	 */
	public static function getSiteUrl( $siteId, $pageTitle = '' ) {
		$ids = WBSettings::get( 'siteIdentifiers' );

		if ( !array_key_exists( $siteId, $ids ) ) {
			return false;
		}
		
		return str_replace( '$1', $pageTitle, $ids[$siteId] );
	}

}
