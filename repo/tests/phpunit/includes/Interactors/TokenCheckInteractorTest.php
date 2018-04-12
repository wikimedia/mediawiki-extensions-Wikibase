<?php

namespace Wikibase\Repo\Tests\Interactors;

use FauxRequest;
use PHPUnit4And6Compat;
use User;
use Wikibase\Repo\Interactors\TokenCheckException;
use Wikibase\Repo\Interactors\TokenCheckInteractor;

/**
 * @covers Wikibase\Repo\Interactors\TokenCheckInteractor
 *
 * @group Wikibase
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class TokenCheckInteractorTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @return User
	 */
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
		$data = [
			'tokentest' => 'VALID'
		];

		$user = $this->getMockUser();
		$request = new FauxRequest( $data, true );

		$checker = new TokenCheckInteractor( $user );
		$checker->checkRequestToken( $request, 'tokentest' );

		$this->assertTrue( true ); // make PHPUnit happy
	}

	public function tokenFailureProvider() {
		return [
			'missingtoken' => [ [ 'foo' => 'VALID' ], true, 'missingtoken' ],
			'mustposttoken' => [ [ 'tokentest' => 'VALID' ], false, 'mustposttoken' ],
			'badtoken' => [ [ 'tokentest' => 'BAD' ], true, 'badtoken' ],
		];
	}

	/**
	 * @dataProvider tokenFailureProvider
	 */
	public function testCheckToken_failure( $data, $wasPosted, $expected ) {
		$request = new FauxRequest( $data, $wasPosted );

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
