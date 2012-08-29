<?php

namespace Wikibase;
use Sanitizer, UtfNormal, Language;

/**
 * Utility functions for Wikibase.
 *
 * @since 0.1
 *
 * @file
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
	 * @see \Language::fetchLanguageName()
	 *
	 * @since 0.1
	 *
	 * @param string $languageCode
	 *
	 * @return string
	 */
	public static function fetchLanguageName( $languageCode ) {
		$languageName = \Language::fetchLanguageName( str_replace( '_', '-', $languageCode ) );
		if ( $languageName == '' ) {
			$languageName = $languageCode;
		}
		return $languageName;
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

		$languages = \FormatJson::decode(
			\Http::get( 'http://meta.wikimedia.org/w/api.php?action=sitematrix&format=json' ),
			true
		);

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
					Sites::newSite( array(
						'global_key' => $site['dbname'],
						'type' => SITE_TYPE_MEDIAWIKI,
						'group' => $groupMap[$site['code']],
						'url' => $site['url'],
						'page_path' => '/wiki/$1',
						'file_path' => '/w/$1',
						'local_key' => ($site['code'] === 'wiki') ? $languageCode : $site['dbname'] ,
						'language' => $languageCode,
						'link_inline' => true,
						'link_navigation' => true,
						'forward' => true,
					) )->save();
				}
			}
		}

		wfGetDB( DB_MASTER )->commit();
	}

	/**
	 * Inserts sites into the database for the unit tests that need them.
	 *
	 * @since 0.1
	 */
	public static function insertSitesForTests() {
		$dbw = wfGetDB( DB_MASTER );

		$dbw->begin( __METHOD__ );

		$dbw->delete( $dbw->tableName( 'sites' ), '*', __METHOD__ );

		Sites::clearCache();

		Sites::newSite( array(
			'global_key' => 'enwiki',
			'type' => SITE_TYPE_MEDIAWIKI,
			'group' => SITE_GROUP_WIKIPEDIA,
			'url' => 'https://en.wikipedia.org',
			'page_path' => '/wiki/$1',
			'file_path' => '/w/$1',
			'local_key' => 'en',
			'language' => 'en',
		) )->save();

		Sites::newSite( array(
			'global_key' => 'dewiki',
			'type' => SITE_TYPE_MEDIAWIKI,
			'group' => SITE_GROUP_WIKIPEDIA,
			'url' => 'https://de.wikipedia.org',
			'page_path' => '/wiki/$1',
			'file_path' => '/w/$1',
			'local_key' => 'de',
			'language' => 'de',
		) )->save();

		Sites::newSite( array(
			'global_key' => 'nlwiki',
			'type' => SITE_TYPE_MEDIAWIKI,
			'group' => SITE_GROUP_WIKIPEDIA,
			'url' => 'https://nl.wikipedia.org',
			'page_path' => '/wiki/$1',
			'file_path' => '/w/$1',
			'local_key' => 'nl',
			'link_inline' => true,
			'link_navigation' => true,
			'forward' => true,
			'language' => 'nl',
		) )->save();

		Sites::newSite( array(
			'global_key' => 'svwiki',
			'url' => 'https://sv.wikipedia.org',
			'page_path' => '/wiki/$1',
			'file_path' => '/w/$1',
			'local_key' => 'sv',
			'language' => 'sv',
		) )->save();


		Sites::newSite( array(
			'global_key' => 'srwiki',
			'type' => 0,
			'group' => 0,
			'url' => 'https://sr.wikipedia.org',
			'page_path' => '/wiki/$1',
			'file_path' => '/w/$1',
			'local_key' => 'sr',
			'link_inline' => true,
			'link_navigation' => true,
			'forward' => true,
		) )->save();

		Sites::newSite( array(
			'global_key' => 'nowiki',
			'type' => 0,
			'group' => 0,
			'url' => 'https://no.wikipedia.org',
			'page_path' => '/wiki/$1',
			'file_path' => '/w/$1',
			'local_key' => 'no',
			'link_inline' => true,
			'link_navigation' => true,
			'forward' => true,
		) )->save();

		Sites::newSite( array(
			'global_key' => 'nnwiki',
			'url' => 'https://nn.wikipedia.org',
			'page_path' => '/wiki/$1',
			'file_path' => '/w/$1',
			'local_key' => 'nn',
			'language' => 'nn',
		) )->save();

		Sites::newSite( array(
			'global_key' => 'enwiktionary',
			'url' => 'https://en.wiktionary.org',
			'page_path' => '/wiki/$1',
			'file_path' => '/w/$1',
			'local_key' => 'enwiktionary',
			'language' => 'en',
		) )->save();

		$dbw->commit( __METHOD__ );
	}

	/**
	 * Trim initial and trailing whitespace, and compress internal ones.
	 *
	 * @since 0.1
	 *
	 * @param string $inputString The actual string to process.
	 *
	 * @return string where whitespace possibly are removed.
	 */
	static public function squashWhitespace( $inputString ) {
		return preg_replace( '/(\s+)/', ' ', preg_replace( '/(^\s+|\s+$)/', '', $inputString ) );
		//return preg_replace( '/(^\s+|\s+$)/', '', Sanitizer::normalizeWhitespace( $inputString ) );
	}

	/**
	 * Normalize string into NFC by using the cleanup metod from UtfNormal.
	 *
	 * @since 0.1
	 *
	 * @param string $inputString The actual string to process.
	 *
	 * @return string where whitespace possibly are removed.
	 */
	static public function cleanupToNFC( $inputString ) {
		return UtfNormal::cleanUp( $inputString );
	}

	/**
	 * Do a toNFC after the string is squashed
	 *
	 * @since 0.1
	 *
	 * @param string $inputString
	 *
	 * @return string on NFC form
	 */
	static public function squashToNFC( $inputString ) {
		return self::cleanupToNFC( self::squashWhitespace( $inputString ) );
	}

	/**
	 * Reorder an array with keys with the order given by a second array.
	 * 
	 * Note that this function will do an intersection and then organize
	 * the resulting array in the order given by the array in the second
	 * argument. The sorting is not by the keys, but by the order the
	 * entries are inserted into the resulting array. Another way to
	 * describe this is to change the insertion order of the first array
	 * according to the sequence of values in the second array.
	 *
	 * @since 0.1
	 *
	 * @param array $array
	 * @param array $sequence
	 *
	 * @return array
	 */
	static public function reorderArray( array $array, array $sequence ) {

		// First create an intersection with our wanted entries as keys
		$common = array_intersect_key( array_flip( $sequence ), $array );

		// Then do a merge with our previous array, and with a new intersection
		return array_merge( $common, array_intersect_key( $array, $common ) );
	}

	/**
	 * Find the multilingual texts that has keys in the the sequence.
	 *
	 * The final result will be in the order given by the sequence.
	 *
	 * @since 0.1
	 *
	 * @param array $texts the key-value pairs to check for existence
	 * @param array $sequence the list of keys that should exist
	 *
	 * @return array
	 */
	static public function filterMultilangText( array $texts = null, array $sequence = null ) {

		// Prerequisites for further processing
		if ( is_null( $texts ) || is_null( $sequence ) ) {
			return array(); // makes the simplest use case
		}

		// Do a reordering to get the language strings in correct order
		$texts = \Wikibase\Utils::reorderArray(
			$texts,
			$sequence
		);

		// Extract the valid codes
		$validCodes = array_filter(
			array_keys( $texts ),
			function( $langCode ) { return is_string( $langCode ) && Language::isValidCode( $langCode ); }
		);

		// If the valid codes are empty we don't need to process it further
		if ( empty( $validCodes ) ) {
			return array();
		}

		// Filter out everything that matches with a key before we return the result
		return array_intersect_key( $texts, array_flip( $validCodes ) );
	}

	/**
	 * Find the first multilingual string that can be used for constructing a language object. The
	 * global chain is always used.
	 *
	 * Note that a multilingual string from the global chain will always be globally cachable.
	 *
	 * @since 0.1
	 *
	 * @param array $texts the key-value pairs to check for existence
	 * @param array $sequence the list of keys that should exist
	 * @param array $fallback an array of values that are used as a replacement if nothing is found
	 * 		The fallback is in the form array( code, text, language )
	 * @return array|null triplet with the initial language code, the text, and the language object
	 */
	static public function lookupMultilangText( array $texts = null, array $sequence = null, array $fallback = null ) {

		// Prerequisites for further processing
		if ( is_null( $texts ) || is_null( $sequence ) ) {
			return $fallback; // makes the simplest use case
		}

		// Filter down the result
		$texts = \Wikibase\Utils::filterMultilangText( $texts, $sequence );
		if ( is_null( $texts ) || empty( $texts ) ) {
			return $fallback;
		}

		// Find the first language code we can turn into a language object
		// Note that the factory call do a pretty dumb cleaning up that can make this vejjy slow
		foreach ( $texts as $code => $text ) {
			$lang = Language::factory( $code );
			if ( !is_null( $lang ) ) {
				return array( $code, $text, $lang );
			}
		}

		// Use the fallback if the previous fails
		return $fallback;
	}

	/**
	 * Find the first multilingual string that can be used for constructing a language object
	 * for the current user. If a preferred language can't be identified the global chain is
	 * used.
	 *
	 * Note that a user specific multilingual string is not globally cachable.
	 *
	 * FIXME: duplication with @see lookupMultilangText, needs refactor
	 *
	 * @since 0.1
	 *
	 * @param array $texts the key-value pairs to check for existence
	 * @param array $sequence the list of keys that should exist
	 * @param array $fallback an array of values that are used as a replacement if nothing is found
	 * 		The fallback is in the form array( code, text, language )
	 * @return array|null triplet with the initial language code, the text, and the language object
	 */
	static public function lookupUserMultilangText( array $texts = null, array $sequence = null, array $fallback = null ) {
		// FIXME: deprecated globals!
		global $wgUser, $wgLang;

		// Prerequisites for further processing
		if ( is_null( $texts ) || is_null( $sequence ) ) {
			return $fallback; // makes the simplest use case
		}

		// Filter down the result
		$texts = \Wikibase\Utils::filterMultilangText( $texts, $sequence );
		if ( is_null( $texts ) || empty( $texts ) ) {
			return $fallback;
		}

		// Check if we can use the ordinary language
		// This should always be used if possible because this will match
		// with the user set language
		reset($texts);
		list( $code, $text ) = each( $texts );
		if ( $wgLang->getCode() === $code ) {
			$lang = Language::factory( $code );
			if ( !is_null( $lang ) ) {
				return array( $code, $text, $lang );
			}
		}

		// Find the first preferred language code we can turn into a language object
		// Note that the factory call do a pretty dumb cleaning up that can make this vejjy slow
		foreach ( $texts as $code => $text ) {
			if ( $wgUser->getOption( "sttl-languages-$code" ) ) {
				$lang = Language::factory( $code );
				if ( !is_null( $lang ) ) {
					return array( $code, $text, $lang );
				}
			}
		}

		// Find the first ordinary language code we can turn into a language object
		// Note that the factory call do a pretty dumb cleaning up that can make this vejjy slow
		foreach ( $texts as $code => $text ) {
			$lang = Language::factory( $code );
			if ( !is_null( $lang ) ) {
				return array( $code, $text, $lang );
			}
		}

		// Use the fallback if the previous fails
		return $fallback;
	}

	/**
	 * Get the fallback languages prepended with the source language itself.
	 *
	 * A language chain in this respect is the language itself and all fallback
	 * languagese. Because English is prepended to all languages it is not a real
	 * language group, its only a language group for the purpose of figuring out
	 * the best guess if language attributes are missing.
	 *
	 * Note that a language chain is globally unique, there will not be any
	 * language with two different chains.
	 *
	 * @since 0.1
	 *
	 * @param string $langCode the language code for the source language itself
	 * @return array of language codes
	 */
	static public function languageChain( $langCode ) {
		return array_merge( array( $langCode ), Language::getFallbacksFor( $langCode ) );
	}

	/**
	 * Generates and returns a GUID.
	 * @see http://php.net/manual/en/function.com-create-guid.php
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public static function getGUID() {
		if ( function_exists( 'com_create_guid' ) ) {
			return trim( com_create_guid(), '{}' );
		}

		return sprintf(
			'%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
			mt_rand( 0, 65535 ),
			mt_rand( 0, 65535 ),
			mt_rand( 0, 65535 ),
			mt_rand( 16384, 20479 ),
			mt_rand( 32768, 49151 ),
			mt_rand( 0, 65535 ),
			mt_rand( 0, 65535 ),
			mt_rand( 0, 65535 )
		);
	}

}
