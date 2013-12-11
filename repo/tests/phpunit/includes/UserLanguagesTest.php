<?php

namespace Wikibase\Test;
use User;
use Wikibase\UserLanguages;

/**
 * @covers Wikibase\UserLanguages
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group EntityView
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class UserLanguagesTest extends \PHPUnit_Framework_TestCase {

	public function testGetLanguages() {

		$user = new User();
		$user->setName( 'UserLanguagesTest-TestUser' );
		$user->setOption( 'language', 'de' );

		$userLanguages = new UserLanguages( $user );

		//TODO: we really want to test grabbing languages from the Babel extension,
		//      but how can we test that?

		$this->assertContains( 'de', $userLanguages->getLanguages() );
		$this->assertNotContains( 'de', $userLanguages->getLanguages( array( 'de' ) ) );
	}

}
