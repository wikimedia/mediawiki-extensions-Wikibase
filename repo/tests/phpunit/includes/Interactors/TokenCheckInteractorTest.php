<?php

namespace Wikibase\Test\Interactors;

use User;
use Wikibase\Repo\Interactors\TokenCheckException;
use Wikibase\Repo\Interactors\TokenCheckInteractor;

/**
 * @covers Wikibase\Repo\Interactors\TokenCheckInteractor
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseInteractor
 *
 * @group medium
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class TokenCheckInteractorTest extends \PHPUnit_Framework_TestCase {

	private function getMockUser() {
		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();
		$user->expects( $this->any() )
			->method( 'matchEditToken' )
			->will( $this->returnCallback(
				function ( $token ) {
					return $token === 'VALID';
				}
			) );

		return $user;
	}

	public function testCheckToken() {
		$data = array(
			'tokentest' => 'VALID'
		);

		$user = $this->getMockUser();
		$request = new \FauxRequest( $data, true );

		$checker = new TokenCheckInteractor( $user );
		$checker->checkRequestToken( $request, 'tokentest' );

		$this->assertTrue( true ); // make PHPUnit happy
	}

	public function tokenFailureProvider() {
		return array(
			'missingtoken' => array( array( 'foo' => 'VALID' ), true, 'missingtoken' ),
			'mustposttoken' => array( array( 'tokentest' => 'VALID' ), false, 'mustposttoken' ),
			'badtoken' => array( array( 'tokentest' => 'BAD' ), true, 'badtoken' ),
		);
	}

	/**
	 * @dataProvider tokenFailureProvider
	 */
	public function testCheckToken_failure( $data, $wasPosted, $expected ) {
		$request = new \FauxRequest( $data, $wasPosted );

		$user = $this->getMockUser();
		$checker = new TokenCheckInteractor( $user );

		try {
			$checker->checkRequestToken( $request, 'tokentest' );
			$this->fail( 'check did not throw a TokenCheckException as expected' );
		} catch ( TokenCheckException $ex ) {
			$this->assertEquals( $expected, $ex->getErrorCode() );
		}
	}

}
