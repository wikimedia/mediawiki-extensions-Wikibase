<?php

namespace Wikibase\Repo;

/**
 * Utility class for process identifier (PID) locking.
 *
 * @license GPL-2.0-or-later
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
	 * @param string|null $wikiId the wiki's id, used for per-wiki file names. Defaults to wfWikiID().
	 */
	public function __construct( $module, $wikiId = null ) {
		$this->module = $module;
		$this->wikiId = $wikiId;
	}

	/**
	 * Check the given process identifier to see if it is alive.
	 *
	 * @param int $pid the process identifier to check
	 *
	 * @return bool true if the process exist
	 */
	private function isAlive( $pid ) {
		// Are we anything but Windows, i.e. some kind of Unix?
		if ( strtoupper( substr( PHP_OS, 0, 3 ) ) !== 'WIN' ) {
			return !!posix_getsid( $pid );
		}

		$processes = explode( "\n", shell_exec( 'tasklist.exe' ) );
		if ( is_array( $processes ) ) {
			foreach ( $processes as $process ) {
				if ( strpos( 'Image Name', $process ) === 0 || strpos( '===', $process ) === 0 ) {
					continue;
				}

				if ( preg_match( '/\d+/', $process, $matches )
					&& (int)$pid === (int)$matches[0]
				) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Tries to allocate a process identifier based lock for the process, to avoid running more than
	 * one instance.
	 *
	 * Note that this method creates the file if necessary.
	 *
	 * @param bool $force make the function skip the test and always grab the lock
	 *
	 * @return bool true if we got the lock, i.e. if no instance is already running,
	 *         or $force was set.
	 */
	public function getLock( $force = false ) {
		$file = $this->getStateFile();

		if ( $force !== true ) {
			// check if the process still exist and is alive
			// XXX: there's a race condition here.
			if ( file_exists( $file ) ) {
				$pid = (int)file_get_contents( $file );
				if ( $this->isAlive( $pid ) === true ) {
					return false;
				}
			}
		}

		file_put_contents( $file, getmypid() );

		return true;
	}

	/**
	 * Remove the process identifier lock. Assumes that we hold it.
	 *
	 * @return bool Success
	 */
	public function removeLock() {
		return unlink( $this->getStateFile() );
	}

	/**
	 * Generate a name and path for a file to store some kind of state in.
	 *
	 * @return string File path
	 */
	private function getStateFile() {
		$fileName = preg_replace( '/[^a-z\d]+/i', '', $this->module ) . '_'
			. preg_replace( '/[^a-z\d]+/i', '', $this->wikiId ?: wfWikiID() ) . '.pid';

		// Directory /var/run/ with system specific separators
		$dir = DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'run' . DIRECTORY_SEPARATOR;
		$file = $dir . $fileName;

		// Let's see if we can write to the file in /var/run
		if ( !is_writable( $file ) && ( !is_dir( $dir ) || !is_writable( $dir ) ) ) {
			// else use the temporary directory
			$file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;
		}

		return $file;
	}

}
