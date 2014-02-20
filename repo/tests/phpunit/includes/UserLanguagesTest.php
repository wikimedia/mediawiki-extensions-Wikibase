<?php

namespace Wikibase\Test;

use User;

/**
 * @covers Wikibase\UserLanguages
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group EntityView
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class UserLanguagesTest extends \PHPUnit_Framework_TestCase {

	public function testGetUserLanguages() {
		$user = new User();
		// Required to not be anonymous
		$user->setId( 1 );
		$user->setName( 'UserLanguagesTest-TestUser' );

		$userLanguageLookup = new UserLanguageLookupDouble( $user );

		// TODO: We really want to test grabbing languages from the Babel extension,
		// but how can we test that?

		$tests = array(
			// 1. Language from the users settings
			// 2. List of languages from the users babel box
			// 3. Expected collection of all languages
			// 4. Expected extra languages excluding de
			// 5. Expected extra languages excluding en
			array( 'de', '',         'de',       '',      'de'    ),
			array( 'de', 'en',       'de,en',    'en',    'de'    ),
			array( 'de', 'de,en,fr', 'de,en,fr', 'en,fr', 'de,fr' ),
			array( 'en', '',         'en',       'en',    ''      ),
			array( 'en', 'en',       'en',       'en',    ''      ),
			array( 'en', 'de,en,fr', 'en,de,fr', 'en,fr', 'de,fr' )
		);

		foreach ( $tests as $test ) {
			list( $usersLanguage, $babelLanguages, $allExpected, $expectedWithoutDe,
				$expectedWithoutEn ) = $test;

			$message = $usersLanguage . ' with ' .
				( $babelLanguages ? 'no/empty' : $babelLanguages ) . ' Babel in assert #';

			$babelLanguages    = preg_split( '/,/', $babelLanguages,    0, PREG_SPLIT_NO_EMPTY );
			$allExpected       = preg_split( '/,/', $allExpected,       0, PREG_SPLIT_NO_EMPTY );
			$expectedWithoutDe = preg_split( '/,/', $expectedWithoutDe, 0, PREG_SPLIT_NO_EMPTY );
			$expectedWithoutEn = preg_split( '/,/', $expectedWithoutEn, 0, PREG_SPLIT_NO_EMPTY );
			$hasBabelLanguages = !empty( $babelLanguages );

			$user->setOption( 'language', $usersLanguage );
			// Not a real option, just to manipulate the double class
			$user->setOption( 'babelLanguages', $babelLanguages );

			$this->assertEquals( $allExpected, array_values(
				$userLanguageLookup->getAllUserLanguages() ), $message . '1' );
			$this->assertEquals( $expectedWithoutDe, array_values(
				$userLanguageLookup->getExtraUserLanguages( array( 'de' ) ) ), $message . '2' );
			$this->assertEquals( $expectedWithoutEn, array_values(
				$userLanguageLookup->getExtraUserLanguages( array( 'en' ) ) ), $message . '3' );
			$this->assertEquals( $hasBabelLanguages,
				$userLanguageLookup->hasSpecifiedAlternativeLanguages(), $message . '4' );
		}
	}

}
