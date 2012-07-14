<?php

namespace Wikibase;
use Sanitizer, UtfNormal;

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

		$dbw->query( 'TRUNCATE TABLE ' . $dbw->tableName( 'sites' ), __METHOD__ );

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
	 * @return filtered string where whitespace possibly are removed.
	 */
	static public function squashWhitespace( $inputString ) {
		return preg_replace( '/(\s+)/', ' ', preg_replace( '/(^\s+|\s+$)/', '', $inputString ) );
		//return preg_replace( '/(^\s+|\s+$)/', '', Sanitizer::normalizeWhitespace( $inputString ) );
	}

	/**
	 * Normalize string into NFC after first checkingh if its already normalized.
	 *
	 * @since 0.1
	 *
	 * @param string $inputString The actual string to process.
	 * @return filtered string where whitespace possibly are removed.
	 */
	static public function conditionalToNFC( $inputString ) {
		// Note that quickIsNFCVerify will do some cleanup of the string,
		// but if we fail to detect a legal string, then we convert
		// the filtered string anyhow.
		if ( !UtfNormal::quickIsNFCVerify( $inputString ) ) {
			return UtfNormal::toNFC( $inputString );
		}
		return $inputString;
	}

	/**
	 * Normalize string into NFC by using the cleanup metod from UtfNormal.
	 *
	 * @since 0.1
	 *
	 * @param string $inputString The actual string to process.
	 * @return filtered string where whitespace possibly are removed.
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
	 * @return trimmed string on NFC form
	 */
	static public function squashToNFC( $inputString ) {
		//return self::conditionalToNFC( self::squashWhitespace( $inputString ) );
		return self::cleanupToNFC( self::squashWhitespace( $inputString ) );
	}

	/**
	 * Truncate a string to a specified length in bytes, appending an optional
	 * string (e.g. for ellipses)
	 *
	 * This is nearly the same as in Language.php
	 *
	 * If $length is negative, the string will be truncated from the beginning
	 *
	 * @param $string String to truncate
	 * @param $length Int: maximum length (including ellipses)
	 * @param $adjustLength Boolean: Subtract length of ellipsis from $length.
	 *	$adjustLength was introduced in 1.18, before that behaved as if false.
	 * @return string
	 */
	public static function truncate( $string, $length, $adjustLength = true ) {
		# Check if there is no need to truncate
		if ( $length == 0 ) {
			return SUMMARY_CONTINUATION; // convention
		} elseif ( strlen( $string ) <= abs( $length ) ) {
			return $string; // no need to truncate
		}
		$stringOriginal = $string;
		# If ellipsis length is >= $length then we can't apply $adjustLength
		if ( $adjustLength && strlen( SUMMARY_CONTINUATION ) >= abs( $length ) ) {
			$string = SUMMARY_CONTINUATION; // this can be slightly unexpected
		# Otherwise, truncate and add ellipsis...
		} else {
			$eLength = $adjustLength ? strlen( SUMMARY_CONTINUATION ) : 0;
			if ( $length > 0 ) {
				$length -= $eLength;
				$string = substr( $string, 0, $length ); // xyz...
				$string = self::removeBadCharLast( $string );
				$string = rtrim( $string, SUMMARY_ESCAPE );
				$string = $string . SUMMARY_CONTINUATION;
			} else {
				$length += $eLength;
				$string = substr( $string, $length ); // ...xyz
				$string = self::removeBadCharFirst( $string );
				$string = SUMMARY_CONTINUATION . $string;
			}
		}
		# Do not truncate if the ellipsis makes the string longer/equal (bug 22181).
		# This check is *not* redundant if $adjustLength, due to the single case where
		# LEN($ellipsis) > ABS($limit arg); $stringOriginal could be shorter than $string.
		if ( strlen( $string ) < strlen( $stringOriginal ) ) {
			return $string;
		} else {
			return $stringOriginal;
		}
	}

	/**
	 * Remove bytes that represent an incomplete Unicode character
	 * at the end of string (e.g. bytes of the char are missing)
	 *
	 * @param $string String
	 * @return string
	 */
	public static function removeBadCharLast( $string ) {
		if ( $string != '' ) {
			$char = ord( $string[strlen( $string ) - 1] );
			$m = array();
			if ( $char >= 0xc0 ) {
				# We got the first byte only of a multibyte char; remove it.
				$string = substr( $string, 0, -1 );
			} elseif ( $char >= 0x80 &&
				  preg_match( '/^(.*)(?:[\xe0-\xef][\x80-\xbf]|' .
							  '[\xf0-\xf7][\x80-\xbf]{1,2})$/', $string, $m ) )
			{
				# We chopped in the middle of a character; remove it
				$string = $m[1];
			}
		}
		return $string;
	}

	/**
	 * Remove bytes that represent an incomplete Unicode character
	 * at the start of string (e.g. bytes of the char are missing)
	 *
	 * @param $string String
	 * @return string
	 */
	public static function removeBadCharFirst( $string ) {
		if ( $string != '' ) {
			$char = ord( $string[0] );
			if ( $char >= 0x80 && $char < 0xc0 ) {
				# We chopped in the middle of a character; remove the whole thing
				$string = preg_replace( '/^[\x80-\xbf]+/', '', $string );
			}
		}
		return $string;
	}

}
