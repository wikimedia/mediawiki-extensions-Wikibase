<?php

namespace Wikibase\Repo\Tests;

use MediaWikiIntegrationTestCase;
use User;

/**
 * @covers \Wikibase\Repo\BabelUserLanguageLookup
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class BabelUserLanguageLookupTest extends MediaWikiIntegrationTestCase {

	/**
	 * @param string $subject
	 *
	 * @return string[]
	 */
	private function split( $subject ) {
		return empty( $subject ) ? [] : explode( '|', $subject );
	}

	/**
	 * TODO: We really want to test grabbing languages from the Babel extension,
	 * but how can we test that?
	 *
	 * @dataProvider userLanguagesProvider
	 *
	 * @param string $usersLanguage
	 * @param string $babelLanguages
	 * @param string $userSpecifiedLanguages
	 * @param string $allExpected
	 */
	public function testGetUserLanguages(
		$usersLanguage,
		$babelLanguages,
		$userSpecifiedLanguages,
		$allExpected
	) {
		$message = $usersLanguage . ' with {{#babel:' . $babelLanguages . '}} in assert #';

		$babelLanguages = $this->split( $babelLanguages );
		$userSpecifiedLanguages = $this->split( $userSpecifiedLanguages );
		$allExpected = $this->split( $allExpected );

		$user = new User();
		// Required to not be anonymous
		$user->setId( 1 );
		$user->setName( 'UserLanguageLookupTest-TestUser' );
		$userOptionsManager = $this->getServiceContainer()->getUserOptionsManager();
		$userOptionsManager->setOption( $user, 'language', $usersLanguage );
		// Not a real option, just to manipulate the double class
		$userOptionsManager->setOption( $user, 'babelLanguages', $babelLanguages );
		$userLanguageLookup = new BabelUserLanguageLookupDouble( $user );

		$this->assertEquals( $allExpected,
			$userLanguageLookup->getAllUserLanguages( $user ), $message . '1' );
		$this->assertEquals( $userSpecifiedLanguages,
			$userLanguageLookup->getUserSpecifiedLanguages( $user ), $message . '5' );
	}

	public function userLanguagesProvider() {
		return [
			// 0. Language from the users settings
			// 1. List of languages from the users babel box (as returned by the Babel extension)
			// 2. List of usable user specified languages
			// 3. Expected collection of all languages
			[ 'de', '', '', 'de' ],
			[ 'de', 'en', 'en', 'de|en' ],
			[ 'de', 'de|en|fr', 'de|en|fr', 'de|en|fr' ],
			[ 'en', '', '', 'en' ],
			[ 'en', 'en', 'en', 'en' ],
			[ 'en', 'de|en|fr', 'de|en|fr', 'en|de|fr' ],

			// Codes reported from Babel are getting lower-cased
			[ 'en', 'nds-NL', 'nds-nl', 'en|nds-nl' ],

			// Whatever we get from Babel will be retained
			[ 'en', 'invalid-language-code', 'invalid-language-code', 'en|invalid-language-code' ],
		];
	}

}
