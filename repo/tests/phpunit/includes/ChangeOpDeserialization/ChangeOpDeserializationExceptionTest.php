<?php

namespace Wikibase\Repo\Tests\ChangeOpDeserialization;

use Wikibase\Repo\ChangeOpDeserialization\ChangeOpDeserializationException;

/**
 * @covers Wikibase\Repo\ChangeOpDeserialization\ChangeOpDeserializationException
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
		$exception = new ChangeOpDeserializationException( $message, 'bar' );

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
		return [ [ 'foo' ], [ 'bar' ] ];
	}

}
