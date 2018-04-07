<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use PHPUnit\Framework\Assert;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;

/**
 * @license GPL-2.0-or-later
 */
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
