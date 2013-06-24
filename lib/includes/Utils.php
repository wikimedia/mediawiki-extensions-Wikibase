<?php

namespace Wikibase;
use Sanitizer, UtfNormal, Language, SiteList, SiteSQLStore;

/**
 * Utility functions for Wikibase.
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
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author John Erling Blad < jeblad@gmail.com >
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
	 * @param string|null $inLanguage
	 *
	 * @return string
	 */
	public static function fetchLanguageName( $languageCode, $inLanguage = null ) {
		$languageCode = str_replace( '_', '-', $languageCode );
		if ( isset( $inLanguage ) ) {
			$inLanguage = str_replace( '_', '-', $inLanguage );
			$languageName = \Language::fetchLanguageName( $languageCode, $inLanguage );
		}
		else {
			$languageName = \Language::fetchLanguageName( $languageCode );
		}
		if ( $languageName == '' ) {
			$languageName = $languageCode;
		}
		return $languageName;
	}

	/**
	 * Inserts some sites into the sites table, if the sites table is currently empty.
	 * Called when update.php is run. The initial sites are loaded from https://meta.wikimedia.org.
	 *
	 * @param \DatabaseUpdater $updater database updater. Not used. Present to be compatible with DatabaseUpdater::addExtensionUpdate
	 *
	 * @throws \MWException if an error occurs.
	 * @since 0.1
	 */
	public static function insertDefaultSites( $updater = null ) {
		if ( \Sites::singleton()->getSites()->count() > 0 ) {
			return;
		}

		self::insertSitesFrom( 'https://meta.wikimedia.org/w/api.php' );
	}

	/**
	 * Inserts sites from another wiki into the sites table. The other wiki must run the
	 * WikiMatrix extension. Existing entries in the sites table are not modified.
	 *
	 * @note This should move into core, together with the populateSitesTable.php script.
	 *
	 * @param String           $url     The URL of the API to fetch the sites from.
	 *                         Defaults to 'https://meta.wikimedia.org/w/api.php'
	 *
	 * @param String|bool      $stripProtocol Causes any leading http or https to be stripped from URLs, forcing
	 *                         the remote sites to be references in a protocol-relative way.
	 *
	 * @throws \MWException if an error occurs.
	 * @since 0.1
	 */
	public static function insertSitesFrom( $url, $stripProtocol = false ) {

		// No sites present yet, fetching from api to populate sites table

		$url .= '?action=sitematrix&format=json';

		//NOTE: the raiseException option needs change Iad3995a6 to be merged, otherwise it is ignored.
		$json = \Http::get( $url, 'default', array( 'raiseException' => true ) );

		if ( !$json ) {
			throw new \MWException( "Got no data from $url" );
		}

		$languages = \FormatJson::decode(
			$json,
			true
		);

		if ( !is_array( $languages ) ) {
			throw new \MWException( "Failed to parse JSON from $url" );
		}

		$groupMap = array(
			'wiki' => 'wikipedia',
			'wiktionary' => 'wiktionary',
			'wikibooks' => 'wikibooks',
			'wikiquote' => 'wikiquote',
			'wikisource' => 'wikisource',
			'wikiversity' => 'wikiversity',
			'wikivoyage' => 'wikivoyage',
			'wikinews' => 'wikinews',
		);

		$store = SiteSQLStore::newInstance();

		// make sure we compare against the actual contents of the database
		$sites = $store->getSites( "nocache" );

		$newSites = array();

		// Inserting obtained sites...
		foreach ( $languages['sitematrix'] as $language ) {
			if ( is_array( $language ) && array_key_exists( 'code', $language ) && array_key_exists( 'site', $language ) ) {
				$languageCode = $language['code'];

				foreach ( $language['site'] as $siteData ) {
					if ( $sites->hasSite( $siteData['dbname'] ) ) {
						continue;
					}

					$site = new \MediaWikiSite();
					$site->setGlobalId( $siteData['dbname'] );

					$site->setGroup( $groupMap[$siteData['code']] );
					$site->setLanguageCode( $languageCode );

					$localId = $siteData['code'] === 'wiki' ? $languageCode : $siteData['dbname'];
					$site->addInterwikiId( $localId );
					$site->addNavigationId( $localId );

					$url = $siteData['url'];

					if ( $stripProtocol === 'stripProtocol' ) {
						$url = preg_replace( '@^https?:@', '', $url );
					}

					$site->setFilePath( $url . '/w/$1' );
					$site->setPagePath( $url . '/wiki/$1' );

					$newSites[]= $site;
				}
			}
		}

		$store->saveSites( $newSites );

		wfWaitForSlaves();
	}

	/**
	 * Trim initial and trailing whitespace and control chars, and optionally compress internal ones.
	 *
	 * @since 0.1
	 *
	 * @param string $inputString The actual string to process.
	 *
	 * @return string where whitespace possibly are removed.
	 */
	static public function trimWhitespace( $inputString ) {
		// \p{Z} - whitespace
		// \p{Cc} - control chars
		$trimmed = preg_replace( '/^[\p{Z}\p{Cc}]+|[\p{Z}\p{Cc}]+$/u', '', $inputString );
		$trimmed = preg_replace( '/[\p{Cc}]+/u', ' ', $trimmed );
		return $trimmed;
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
	static public function trimToNFC( $inputString ) {
		return self::cleanupToNFC( self::trimWhitespace( $inputString ) );
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
	 * Check the given PID to see if it is alive
	 *
	 * @since 0.3
	 *
	 * @param int $pid the process identifier to check
	 *
	 * @return boolean true if the process exist
	 */
	public static function isPidAlive( $pid ) {
		// Are we anything but Windows, i.e. some kind of Unix?
		if ( strtoupper( substr( PHP_OS, 0, 3 ) ) !== 'WIN' ) {
			return !!posix_getsid( $pid );
		}
		// Welcome to Redmond
		else {
			$processes = explode( "\n", shell_exec( "tasklist.exe" ) );
			if ( $processes !== false && count( $processes ) > 0 ) {
				foreach( $processes as $process ) {
					if( strlen( $process ) > 0
						&& ( strpos( "Image Name", $process ) === 0
						|| strpos( "===", $process ) === 0 ) ) {
						continue;
					}
					$matches = false;
					preg_match( "/^(\D*)(\d+).*$/", $process, $matches );
					$processid = 0;
					if ( $matches !== false && count ($matches) > 1 ) {
						$processid = $matches[ 2 ];
					}
					if ( $processid === $pid ) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Tries to allocate a PID based lock for the process, to avoid running more than one
	 * instance.
	 *
	 * Note that this method creates the file $pidfile if necessary.
	 *
	 * @since 0.3
	 *
	 * @param string $pidfile the place where the pid is stored
	 * @param boolean $force make the function skip the test and always grab the lock
	 *
	 * @return boolean true if we got the lock, i.e. if no instance is already running,
	 *         or $force was set.
	 */
	public static function getPidLock( $pidfile, $force = false ) {
		if ( $force === true ) {
			file_put_contents( $pidfile, getmypid() );
			return true;
		} else {
			// check if the process still exist and is alive
			// XXX: there's a race condition here.
			if ( file_exists( $pidfile ) ) {
				$pid = file_get_contents( $pidfile );
				if ( Utils::isPidAlive( $pid ) === true ) {
					return false;
				}
			}
			file_put_contents( $pidfile, getmypid() );
			return true;
		}
	}

	/**
	 * Create a pid file name
	 *
	 * @since 0.3
	 *
	 * @param string $module used as a basis for the file name.
	 * @param string $wikiId the wiki's id, used for per-wiki file names. Defaults to wfWikiID().
	 *
	 * @return boolean true if the process exist
	 */
	public static function makePidFilename( $module, $wikiId = null ) {
		return self::makeStateFilename( $module, '.pid', $wikiId );
	}

	/**
	 * Generate a name and path for a file to store some kind of state in.
	 *
	 * @since 0.4
	 *
	 * @param string $module used as a basis for the file name.
	 * @param string $suffix suffix, including file extension, appended without a separator.
	 * @param string $wikiId the wiki's id, used for per-wiki file names. Defaults to wfWikiID().
	 *
	 * @return boolean true if the process exist
	 */
	public static function makeStateFilename( $module, $suffix, $wikiId = null ) {
		if ( $wikiId === null ) {
			$wikiId = wfWikiID();
		}

		// Build the filename
		$pidfileName = preg_replace('/[^a-z0-9]/i', '', $module ) . '_'
				. preg_replace('/[^a-z0-9]/i', '', $wikiId ) . $suffix;

		$pidfile = '/var/run/' . $pidfileName;

		// Let's see if we can write to the file in /var/run
		if ( is_writable( $pidfile )
			|| ( is_dir( dirname( $pidfile ) )
				&& ( is_writable( dirname( $pidfile ) ) ) ) ) {
			$pidfile = '/var/run/' . $pidfileName;
		} else {
			// else use the temporary directory
			$temp = str_replace( '\\', '/', sys_get_temp_dir() );
			$pidfile = $temp . '/' . $pidfileName;
		}

		return $pidfile;
	}

	/**
	 * Return the appropriate copyright message.
	 *
	 * Note that if this is a wiki using the WikimediaMessages extension (i.e. Wikidata)
	 * it will use the shortcopyrightwarning message from that extension instead.
	 *
	 * @return \Message
	 */
	public static function getRightsWarningMessage() {
		global $wgRightsUrl, $wgRightsText;

		if ( wfMessage( 'wikidata-shortcopyrightwarning' )->exists() ) {
			$rightsWarningMessage = wfMessage( 'wikidata-shortcopyrightwarning' );
		} else {
			$rightsWarningMessage = wfMessage( 'wikibase-shortcopyrightwarning',
				wfMessage( 'wikibase-save' )->inContentLanguage()->text(),
				wfMessage( 'copyrightpage' )->inContentLanguage()->text(),
				"[$wgRightsUrl $wgRightsText]"
			);
		}

		return $rightsWarningMessage;
	}

}
