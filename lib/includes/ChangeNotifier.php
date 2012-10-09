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
		foreach ( $changes as $change ) {
			//XXX: the Change interface does not define save().
			$change->save();
		}

		return \Status::newGood();
	}

}