<?php

namespace Wikibase\Test;

use Wikibase\Badge\BadgeException;

/**
 * @covers Wikibase\Badge\BadgeException
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class BadgeExceptionTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider constructorProvider
	 */
	public function testConstructorWithRequiredArguments( $messageKey, $rawInput, $message ) {
		$exception = new BadgeException( $messageKey, $rawInput, $message );

		$this->assertEquals( $messageKey, $exception->getMessageKey() );
		$this->assertEquals( $rawInput, $exception->getRawInput() );
		$this->assertEquals( $message, $exception->getMessage() );
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testConstructorWithAllArguments( $messageKey, $rawInput, $message ) {
		$previous = new \Exception( 'Onoez!' );

		$exception = new BadgeException(
			$messageKey,
			$rawInput,
			$message,
			$previous
		);

		$this->assertEquals( $messageKey, $exception->getMessageKey() );
		$this->assertEquals( $rawInput, $exception->getRawInput() );
		$this->assertContains( $message, $exception->getMessage() );
		$this->assertEquals( $previous, $exception->getPrevious() );
	}

	public function constructorProvider() {
		return array(
			array( 'wikibase-invalid-badge', 'P9000', 'onoeezz!' )
		);
	}

}
