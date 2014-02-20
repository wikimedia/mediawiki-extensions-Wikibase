<?php

namespace Wikibase\Test;

/**
 * Double for the PHPUnit test that overrides the only method that depends on the Babel extension
 * so we can test everything else.
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class UserLanguageLookupDouble extends \Wikibase\UserLanguageLookup {

	protected function getBabelLanguages() {
		// Not a real option, just to manipulate the double class
		return $this->user->getOption( 'babelLanguages' );
	}

}
