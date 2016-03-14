<?php

namespace Wikibase\Test\Repo\Api;

use ApiBase;
use ApiMain;
use Exception;
use FauxRequest;
use PHPUnit_Framework_Assert as Assert;
use UsageException;
use User;

/**
 * @license GPL-2.0+
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
	 * @param User|null $user Defaults to the global user object
	 *
	 * @return ApiBase
	 */
	public function newApiModule( $instantiator, $name, array $params, User $user = null ) {
		if ( !$user ) {
			$user = $GLOBALS['wgUser'];
		}

		if ( !array_key_exists( 'token', $params ) ) {
			$params['token'] = $user->getToken();
		}

		$request = new FauxRequest( $params, true );
		$main = new ApiMain( $request );
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
		Assert::assertInstanceOf( UsageException::class, $ex );
		/** @var UsageException $ex */

		if ( is_string( $expected ) ) {
			$expected = array( 'code' => $expected );
		}

		if ( isset( $expected['code'] ) ) {
			Assert::assertEquals( $expected['code'], $ex->getCodeString() );
		}

		if ( isset( $expected['message'] ) ) {
			Assert::assertContains( $expected['message'], $ex->getMessage() );
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
