<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Exception;
use PHPUnit_Framework_Assert as Assert;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;

class ChangeOpDeserializationAssert {

	/**
	 * @param callable $callback
	 * @param string $errorCode
	 */
	public static function assertThrowsChangeOpDeserializationException( callable $callback, $errorCode ) {
		try {
			call_user_func( $callback );
		} catch ( ChangeOpDeserializationException $ex ) {
			Assert::assertSame( $errorCode, $ex->getErrorCode() );
			return;
		}

		Assert::fail( 'Expected ChangeOpDeserializationException not thrown' );
	}

}
