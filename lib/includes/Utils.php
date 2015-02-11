<?php

namespace Wikibase;

use Language;
use MWException;

/**
 * Utility functions for Wikibase.
 *
 * @since 0.1
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
	 * @throws MWException if the list can not be obtained.
	 * @return string[]
	 */
	public static function getLanguageCodes() {
		static $languageCodes = null;

		if ( $languageCodes === null ) {
			$languageCodes = array_keys( Language::fetchLanguageNames() );

			if ( empty( $languageCodes ) ) {
				throw new MWException( 'List of language names is empty' );
			}
		}

		return $languageCodes;
	}

	/**
	 * @see Language::fetchLanguageName()
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
			$languageName = Language::fetchLanguageName( $languageCode, $inLanguage );
		}
		else {
			$languageName = Language::fetchLanguageName( $languageCode );
		}

		if ( $languageName == '' ) {
			$languageName = $languageCode;
		}

		return $languageName;
	}

	/**
	 * Check the given PID to see if it is alive
	 *
	 * @param int $pid the process identifier to check
	 *
	 * @return boolean true if the process exist
	 */
	private static function isPidAlive( $pid ) {
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
				if ( self::isPidAlive( $pid ) === true ) {
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
	 * @param string $module used as a basis for the file name.
	 * @param string $suffix suffix, including file extension, appended without a separator.
	 * @param string $wikiId the wiki's id, used for per-wiki file names. Defaults to wfWikiID().
	 *
	 * @return boolean true if the process exist
	 */
	private static function makeStateFilename( $module, $suffix, $wikiId = null ) {
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

}
