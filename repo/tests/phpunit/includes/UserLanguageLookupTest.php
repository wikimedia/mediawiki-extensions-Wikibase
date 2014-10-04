<?php

namespace Wikibase\Test;

use User;

/**
 * @covers Wikibase\UserLanguageLookup
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group EntityView
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class UserLanguageLookupTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @param string $subject
	 *
	 * @return string[]
	 */
	private function split( $subject ) {
		return empty( $subject ) ? array() : explode( '|', $subject );
	}

	/**
	 * TODO: We really want to test grabbing languages from the Babel extension,
	 * but how can we test that?
	 *
	 * @dataProvider userLanguagesProvider
	 *
	 * @param string $usersLanguage
	 * @param string $babelLanguages
	 * @param string $allExpected
	 * @param string $expectedWithoutDe
	 * @param string $expectedWithoutEn
	 */
	public function testGetUserLanguages( $usersLanguage, $babelLanguages, $allExpected,
		$expectedWithoutDe, $expectedWithoutEn ) {
		$babelLanguages    = $this->split( $babelLanguages );
		$allExpected       = $this->split( $allExpected );
		$expectedWithoutDe = $this->split( $expectedWithoutDe );
		$expectedWithoutEn = $this->split( $expectedWithoutEn );
		$hasSpecified      = !empty( $babelLanguages );

		$message = $usersLanguage . ' width {{#babel:' . implode( '|', $babelLanguages ) .
			'}} in assert #';

		$user = new User();
		// Required to not be anonymous
		$user->setId( 1 );
		$user->setName( 'UserLanguageLookupTest-TestUser' );
		$user->setOption( 'language', $usersLanguage );
		// Not a real option, just to manipulate the double class
		$user->setOption( 'babelLanguages', $babelLanguages );
		$userLanguageLookup = new UserLanguageLookupDouble( $user );

		$this->assertEquals( $allExpected, array_values(
			$userLanguageLookup->getAllUserLanguages( $user ) ), $message . '1' );
		$this->assertEquals( $expectedWithoutDe, array_values(
			$userLanguageLookup->getExtraUserLanguages( $user, array( 'de' ) ) ), $message . '2' );
		$this->assertEquals( $expectedWithoutEn, array_values(
			$userLanguageLookup->getExtraUserLanguages( $user, array( 'en' ) ) ), $message . '3' );
		$this->assertEquals( $hasSpecified,
			$userLanguageLookup->hasSpecifiedLanguages( $user ), $message . '4' );
		$this->assertEquals( $babelLanguages,
			$userLanguageLookup->getUserSpecifiedLanguages( $user ), $message . '5' );
	}

	public function userLanguagesProvider() {
		return array(
			// 1. Language from the users settings
			// 2. List of languages from the users babel box
			// 3. Expected collection of all languages
			// 4. Expected extra languages excluding de
			// 5. Expected extra languages excluding en
			array( 'de', '',         'de',       '',      'de'    ),
			array( 'de', 'en',       'de|en',    'en',    'de'    ),
			array( 'de', 'de|en|fr', 'de|en|fr', 'en|fr', 'de|fr' ),
			array( 'en', '',         'en',       'en',    ''      ),
			array( 'en', 'en',       'en',       'en',    ''      ),
			array( 'en', 'de|en|fr', 'de|en|fr', 'en|fr', 'de|fr' ),

			// Codes reported from Babel are getting lower-cased
			array( 'en', 'nds-NL',   'en|nds-nl',       'en|nds-nl', 'nds-nl' ),

			// Invalid codes (codes we don't support) reported from Babel
			// aren't reported
			array( 'en', 'invalid-language-code', 'en', 'en', '' ),
		);
	}

}
