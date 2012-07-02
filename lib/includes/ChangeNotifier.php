<?php

namespace Wikibase;

/**
 * Interface for change notification.
 * Whenever a change is made, it should be fed to this interface
 * so the appropriate notification tasks can be created and run.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ChangeNotifier {

	/**
	 * Returns the global instance of the ChangeNotifier interface.
	 *
	 * @since 0.1
	 *
	 * @return ChangeNotifier
	 */
	public static function singleton() {
		static $instance = false;

		if ( $instance === false ) {
			$instance = new static();
		}

		return $instance;
	}

	/**
	 * @since 0.1
	 * @var bool
	 */
	protected $inTranscation = false;

	/**
	 * The changes stashed in the current transaction.
	 *
	 * @since 0.1
	 * @var array of Change
	 */
	protected $changes = array();

	/**
	 * Begin a transaction.
	 * During the transaction any changes provided will be stashed
	 * and only be committed at the point commit is called.
	 *
	 * @since 0.1
	 */
	public function begin() {
		$this->inTranscation = true;
	}

	/**
	 * Commit all of the stashed changes.
	 *
	 * @since 0.1
	 *
	 * @return \Status
	 */
	public function commit() {
		if ( $this->inTranscation ) {
			$this->inTranscation = false;
			$this->handleChanges( $this->changes );
		}

		return \Status::newGood();
	}

	/**
	 * Returns if a transaction is open or not.
	 *
	 * @since 0.1
	 *
	 * @return bool
	 */
	public function isInTranscation() {
		return $this->inTranscation;
	}

	/**
	 * Handles the provided change.
	 *
	 * @since 0.1
	 *
	 * @param Change $change
	 *
	 * @return \Status
	 */
	public function handleChange( Change $change ) {
		return $this->handleChanges( array( $change ) );
	}

	/**
	 * Handles the provided changes.
	 *
	 * @since 0.1
	 *
	 * @param $changes array of Change
	 *
	 * @return \Status
	 */
	public function handleChanges( array $changes ) {
		if ( $changes !== array() ) {
			if ( $this->inTranscation ) {
				foreach ( $changes as $change ) {
					if ( !$change->isEmpty() ) {
						$this->changes[] = $change;
					}
				}
			}
			else {
				$dbw = wfGetDB( DB_MASTER );

				$dbw->begin();

				foreach ( $changes as $change ) {
					$change->save();
				}

				$dbw->commit();
			}
		}

		return \Status::newGood();
	}

}