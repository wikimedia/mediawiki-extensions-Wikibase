<?php

namespace Wikibase\Lib;

/**
 * Utility class for pid-locking.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class PidLock {

	/**
	 * @var string
	 */
	private $module;

	/**
	 * @var string|null
	 */
	private $wikiId;

	/**
	 * @param string $module used as a basis for the file name.
	 * @param string $wikiId the wiki's id, used for per-wiki file names. Defaults to wfWikiID().
	 */
	public function __construct( $module, $wikiId ) {
		$this->module = $module;
		$this->wikiId = $wikiId;
	}

	/**
	 * Check the given PID to see if it is alive
	 *
	 * @param int $pid the process identifier to check
	 *
	 * @return boolean true if the process exist
	 */
	private function isPidAlive( $pid ) {
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
	 * @since 0.5
	 *
	 * @param boolean $force make the function skip the test and always grab the lock
	 *
	 * @return boolean true if we got the lock, i.e. if no instance is already running,
	 *         or $force was set.
	 */
	public function getPidLock( $force = false ) {
		$pidfile = $this->getStateFile();

		if ( $force !== true ) {
			// check if the process still exist and is alive
			// XXX: there's a race condition here.
			if ( file_exists( $pidfile ) ) {
				$pid = file_get_contents( $pidfile );
				if ( self::isPidAlive( $pid ) === true ) {
					return false;
				}
			}
		}

		file_put_contents( $pidfile, getmypid() );

		return true;
	}

	/**
	 * Remove the pid lock. Assumes that we hold it.
	 *
	 * @return bool Success
	 */
	public function removePidLock() {
		return unlink( $this->getStateFile() );
	}

	/**
	 * Generate a name and path for a file to store some kind of state in.
	 *
	 * @return string File path
	 */
	private function getStateFile() {
		$wikiId = $this->wikiId;
		if ( $wikiId === null ) {
			$wikiId = wfWikiID();
		}

		// Build the filename
		$pidfileName = preg_replace('/[^a-z0-9]/i', '', $this->module ) . '_'
				. preg_replace('/[^a-z0-9]/i', '', $wikiId ) . '.pid';

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
