<?php

namespace Wikibase;

use Wikibase\Repo\WikibaseRepo;

/**
 * Interface for change notification.
 * Whenever a change is made, it should be fed to this interface
 * so the appropriate notification tasks can be created and run.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ChangeNotifier {

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
	 * @param Change[] $changes
	 *
	 * @return \Status
	 */
	public function handleChanges( array $changes ) {
		if ( !WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'useChangesTable' ) ) {
			return \Status::newGood( false );
		}

		foreach ( $changes as $change ) {
			//XXX: the Change interface does not define save().
			$change->save();
		}

		return \Status::newGood( true );
	}

}