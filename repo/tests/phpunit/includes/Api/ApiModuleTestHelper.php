<?php

namespace Wikibase\Repo\Tests\Api;

use ApiBase;
use ApiMain;
use ApiUsageException;
use Exception;
use FauxRequest;
use PHPUnit\Framework\Assert;
use User;
use Wikimedia\TestingAccessWrapper;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ApiModuleTestHelper {

	/**
	 * Instantiates an API module.
	 *
	 * @param callable|string $instantiator A callback or class name for instantiating the module.
	 *        Will be called with two parameters, the ApiMain instance and $name.
	 * @param string $name
	 * @param array $params Request parameter. The 'token' parameter will be supplied automatically.
	 * @param User $user
	 *
	 * @return ApiBase
	 */
	public function newApiModule( $instantiator, $name, array $params, User $user ) {
		if ( !array_key_exists( 'token', $params ) ) {
			$params['token'] = $user->getToken();
		}

		$request = new FauxRequest( $params, true );
		$main = new ApiMain( $request, true );
		$main->getContext()->setUser( $user );

		if ( is_string( $instantiator ) && class_exists( $instantiator ) ) {
			$module = new $instantiator( $main, $name );
		} else {
			$module = call_user_func( $instantiator, $main, $name );
		}

		return $module;
	}

	/**
	 * Asserts that the given API response represents a successful call.
	 *
	 * @param array $response
	 */
	public function assertResultSuccess( array $response ) {
		Assert::assertArrayHasKey( 'success', $response, "Missing 'success' marker in response." );
	}

	/**
	 * @param string|string[] $expected
	 * @param Exception $ex
	 */
	public function assertUsageException( $expected, Exception $ex ) {
		Assert::assertInstanceOf( ApiUsageException::class, $ex );
		/** @var ApiUsageException $ex */

		if ( is_string( $expected ) ) {
			$expected = [ 'code' => $expected ];
		}

		if ( isset( $expected['code'] ) ) {
			$msg = TestingAccessWrapper::newFromObject( $ex )->getApiMessage();
			Assert::assertEquals( $expected['code'], $msg->getApiCode() );
		}

		if ( isset( $expected['message'] ) ) {
			Assert::assertContains( $expected['message'], $ex->getMessage() );
		}

		if ( isset( $expected['extradata'] ) ) {
			$msg = TestingAccessWrapper::newFromObject( $ex )->getApiMessage();
			Assert::assertEquals( $expected['extradata'], $msg->getApiData()['extradata'] );
		}
	}

	/**
	 * Asserts the existence of some path in the result, represented by any additional parameters.
	 *
	 * @param string[] $path
	 * @param array $response
	 */
	public function assertResultHasKeyInPath( array $path, array $response ) {
		$obj = $response;
		$p = '/';

		foreach ( $path as $key ) {
			Assert::assertArrayHasKey( $key, $obj, "Expected key $key under path $p in the response." );

			$obj = $obj[ $key ];
			$p .= "/$key";
		}
	}

}
