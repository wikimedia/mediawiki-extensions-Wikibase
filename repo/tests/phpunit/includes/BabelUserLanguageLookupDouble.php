<?php

namespace Wikibase\Repo\Tests;

use User;
use Wikibase\Repo\BabelUserLanguageLookup;

/**
 * Double for the PHPUnit test that overrides the only method that depends on the Babel extension
 * so we can test everything else.
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 */
class BabelUserLanguageLookupDouble extends BabelUserLanguageLookup {

	protected function getBabelLanguages( User $user ) {
		// Not a real option, just to manipulate the double class
		return $user->getOption( 'babelLanguages' );
	}

}
