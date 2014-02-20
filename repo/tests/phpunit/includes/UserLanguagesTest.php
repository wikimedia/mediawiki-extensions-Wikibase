<?php

namespace Wikibase\Test;

use User;
use Wikibase\UserLanguageLookup;

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
		// Required to not be anon
		$user->setId( 1 );
		$user->setName( 'UserLanguagesTest-TestUser' );
		$user->setOption( 'language', 'de' );

		$userLanguageLookup = new UserLanguageLookup( $user );

		//TODO: we really want to test grabbing languages from the Babel extension,
		//      but how can we test that?

		$this->assertContains( 'de', $userLanguageLookup->getAllUserLanguages() );
		$this->assertNotContains( 'de', $userLanguageLookup->getExtraUserLanguages( array( 'de' ) ) );
		$this->assertEquals( array( 'de' ), $userLanguageLookup->getExtraUserLanguages( array( 'en' ) ) );
		$this->assertFalse( $userLanguageLookup->hasSpecifiedAlternativeLanguages() );
	}

}
