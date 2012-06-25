<?php

namespace Wikibase;

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
 * @author Tobias Gritschacher
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 */
final class Utils {

	/**
	 * Returns a list of language codes that Wikibase supports,
	 * ie the languages that a label or description can be in.
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public static function getLanguageCodes() {
		static $languageCodes = null;

		if ( is_null( $languageCodes ) ) {
			$languageCodes = array_keys( \Language::fetchLanguageNames() );
		}

		return $languageCodes;
	}

	/**
	 * Temporary helper function.
	 * Inserts some sites into the sites table.
	 *
	 * @since 0.1
	 */
	public static function insertDefaultSites() {
		if ( \Wikibase\SitesTable::singleton()->count() > 0 ) {
			return;
		}

        $sitesTable = \Wikibase\SitesTable::singleton();

		// Keep in memory because the tests otherwise generate craptons of HTTP requests.
		static $languages = false;

		if ( $languages === false ) {
			$languages = \FormatJson::decode(
				\Http::get( 'http://meta.wikimedia.org/w/api.php?action=sitematrix&format=json' ),
				true
			);
		}

		wfGetDB( DB_MASTER )->begin();

		$groupMap = array(
			'wiki' => SITE_GROUP_WIKIPEDIA,
			'wiktionary' => SITE_GROUP_WIKTIONARY,
			'wikibooks' => SITE_GROUP_WIKIBOOKS,
			'wikiquote' => SITE_GROUP_WIKIQUOTE,
			'wikisource' => SITE_GROUP_WIKISOURCE,
			'wikiversity' => SITE_GROUP_WIKIVERSITY,
			'wikinews' => SITE_GROUP_WIKINEWS,
		);

		foreach ( $languages['sitematrix'] as $language ) {
			if ( is_array( $language ) && array_key_exists( 'code', $language ) && array_key_exists( 'site', $language ) ) {
				$languageCode = $language['code'];

				foreach ( $language['site'] as $site ) {
					$sitesTable->newFromArray( array(
						'global_key' => $site['dbname'],
						'type' => SITE_TYPE_MEDIAWIKI,
						'group' => $groupMap[$site['code']],
						'url' => $site['url'],
						'page_path' => '/wiki/$1',
						'file_path' => '/w/$1',
						'local_key' => ($site['code'] === 'wiki') ? $languageCode : $site['dbname'] ,
						'link_inline' => true,
						'link_navigation' => true,
						'forward' => true,
						'allow_transclusion' => false,
					) )->save();
				}
			}
		}

		wfGetDB( DB_MASTER )->commit();
	}

	/**
	 * Strips off the "wiki" part of the global site id
	 * to get the language code
	 *
	 * TODO: this is just a tempory solution to get the sitelinks working again
	 * and this will obviously only work for global site ids with the
	 * postfix "wiki", not for e.g. "wiktionary"
	 *
	 * @since 0.1
	 *
	 * @return String
	 */
	public static function getLanguageCodeFromGlobalSiteId( $id ) {
		return preg_replace( "/wiki/", '', $id );
	}

}
