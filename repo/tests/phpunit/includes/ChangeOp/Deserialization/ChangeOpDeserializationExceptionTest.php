<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;

/**
 * @covers \Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpDeserializationExceptionTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider exceptionMessageProvider
	 */
	public function testGetMessage( $message ) {
		$exception = new ChangeOpDeserializationException( $message, 'error-bar' );

		$this->assertSame( $message, $exception->getMessage() );
	}

	public function exceptionMessageProvider() {
		return [ [ 'foo' ], [ 'bar' ] ];
	}

	/**
	 * @dataProvider errorCodeProvider
	 */
	public function testGetCode( $errorCode ) {
		$exception = new ChangeOpDeserializationException( 'foo', $errorCode );

		$this->assertSame( $errorCode, $exception->getErrorCode() );
	}

	public function errorCodeProvider() {
		return [ [ 'error-foo' ], [ 'error-bar' ] ];
	}

	public function testGetParams() {
		$exception = new ChangeOpDeserializationException( 'foo', 'error-foo', [ 'bar', 'baz' ] );

		$this->assertEquals( [ 'bar', 'baz' ], $exception->getParams() );
	}

}
