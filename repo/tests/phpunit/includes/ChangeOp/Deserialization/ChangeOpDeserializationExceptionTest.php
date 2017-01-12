<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;

/**
 * @covers Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class ChangeOpDeserializationExceptionTest extends \PHPUnit_Framework_TestCase {

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
	 * @dataProvider messageKeyProvider
	 */
	public function testGetMessageKey( $messageKey ) {
		$exception = new ChangeOpDeserializationException( 'foo', $messageKey );

		$this->assertSame( $messageKey, $exception->getMessageKey() );
	}

	public function messageKeyProvider() {
		return [ [ 'error-foo' ], [ 'error-bar' ] ];
	}

	public function testGetMessageArgs() {
		$exception = new ChangeOpDeserializationException( 'foo', 'error', [ 'one', 'two' ] );

		$this->assertEquals( [ 'one', 'two' ], $exception->getMessageArgs() );
	}

}
