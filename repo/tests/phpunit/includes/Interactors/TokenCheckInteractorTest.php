<?php

namespace Wikibase\Repo\Tests\Interactors;

use FauxRequest;
use RequestContext;
use User;
use Wikibase\Repo\Interactors\TokenCheckException;
use Wikibase\Repo\Interactors\TokenCheckInteractor;

/**
 * @covers \Wikibase\Repo\Interactors\TokenCheckInteractor
 *
 * @group Wikibase
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class TokenCheckInteractorTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return User
	 */
	private function getMockUser() {
		$user = $this->createMock( User::class );
		$user->method( 'matchEditToken' )
			->willReturnCallback(
				function ( $token ) {
					return $token === 'VALID';
				}
			);

		return $user;
	}

	public function testCheckToken() {
		$data = [
			'tokentest' => 'VALID',
		];

		$context = new RequestContext();
		$context->setRequest( new FauxRequest( $data, true ) );
		$context->setUser( $this->getMockUser() );

		$checker = new TokenCheckInteractor();
		$checker->checkRequestToken( $context, 'tokentest' );

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
		$context = new RequestContext();
		$context->setRequest( new FauxRequest( $data, $wasPosted ) );
		$context->setUser( $this->getMockUser() );

		$checker = new TokenCheckInteractor();

		try {
			$checker->checkRequestToken( $context, 'tokentest' );
			$this->fail( 'check did not throw a TokenCheckException as expected' );
		} catch ( TokenCheckException $ex ) {
			$this->assertEquals( $expected, $ex->getErrorCode() );
		}
	}

}
