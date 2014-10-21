<?php

namespace Wikibase\Test;

use User;
use Wikibase\UserLanguageLookup;

/**
 * Double for the PHPUnit test that overrides the only method that depends on the Babel extension
 * so we can test everything else.
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class UserLanguageLookupDouble extends UserLanguageLookup {

	protected function getBabelLanguages( User $user ) {
		// Not a real option, just to manipulate the double class
		return $user->getOption( 'babelLanguages' );
	}

}
