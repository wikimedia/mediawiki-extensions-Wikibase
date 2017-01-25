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
		$exception = null;

		try {
			call_user_func( $callback );
		} catch ( Exception $e ) {
			$exception = $e;
		}

		/** @var $exception ChangeOpDeserializationException */
		Assert::assertInstanceOf( ChangeOpDeserializationException::class, $exception );
		Assert::assertSame( $errorCode, $exception->getErrorCode() );
	}

}
