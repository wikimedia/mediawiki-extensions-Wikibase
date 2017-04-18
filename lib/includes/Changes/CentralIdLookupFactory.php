<?php

namespace Wikibase;

use CentralIdLookup;

/**
 * @license GPL-2.0+
 * @author Matthew Flaschen < mflaschen@wikimedia.org >
 */
class CentralIdLookupFactory {
	/**
	 * Returns an instance of the factory
	 *
	 * @return CentralIdLookupFactory
	 */
	public static function getInstance() {
		return new CentralIdLookupFactory();
	}

	/**
	 * Returns a CentralIdLookup that is safe to use for cross-wiki propagation, or
	 * null.
	 *
	 * @return CentralIdLookup|null
	 */
	public function getCentralIdLookup() {
		$centralIdLookup = CentralIdLookup::factory();

		if ( $centralIdLookup !== null &&

			// LocalIdLookup is the default for standalone wikis.  However,
			// it will map to the wrong user unless repo and client
			// are both using it, and using the same shared user tables.
			//
			// See also T163277 and https://phabricator.wikimedia.org/T170996 .
			!( $centralIdLookup instanceof LocalIdLookup )
		) {
			return $centralIdLookup;
		}

		return null;
	}
}
