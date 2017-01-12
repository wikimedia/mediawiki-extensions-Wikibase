<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use Wikibase\ChangeOp\ChangeOpException;

/**
 * @covers Wikibase\Repo\ChangeOp\Deserialization\ChangeOpException
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class ChangeOpExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testGetMessage() {
		$exception = new ChangeOpException( 'Some text', 'error-message' );

		$this->assertSame( 'Some text', $exception->getMessage() );
	}

	public function testGetMessageKey() {
		$exception = new ChangeOpException( 'Some text', 'error-message' );

		$this->assertSame( 'error-message', $exception->getMessageKey() );
	}

	public function testGetMessageArgs() {
		$exception = new ChangeOpException( 'Some text', 'error-message', [ 'foo', 'oof' ] );

		$this->assertEquals( [ 'foo', 'oof' ], $exception->getMessageArgs() );
	}

}
