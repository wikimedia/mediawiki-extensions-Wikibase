<?php

namespace Wikibase;

/**
 * Interface for change handling. Whenever a change is detected,
 * it should be fed to this interface which then takes care of
 * notifying all handlers.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ChangeHandler {

	/**
	 * Returns the global instance of the ChangeHandler interface.
	 *
	 * @since 0.1
	 *
	 * @return ChangeHandler
	 */
	public static function singleton() {
		static $instance = false;

		if ( $instance === false ) {
			$instance = new static();
		}

		return $instance;
	}

	/**
	 * Handle the provided changes.
	 *
	 * @since 0.1
	 *
	 * @param $changes array of Change
	 */
	public function handleChanges( array $changes ) {
		wfRunHooks( 'WikibasePollBeforeHandle', array( $changes ) );

		foreach ( $changes as /* WikibaseChange */ $change ) {
			print( "Processing change:\n" );
			print_r( $change );
			wfRunHooks( 'WikibasePollHandle', array( $change ) );
		}

		wfRunHooks( 'WikibasePollAfterHandle', array( $changes ) );
	}

}