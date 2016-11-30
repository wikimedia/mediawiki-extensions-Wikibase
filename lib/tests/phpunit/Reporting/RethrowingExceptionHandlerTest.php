<?php

namespace Wikibase\Lib\Tests\Reporting;

use RuntimeException;
use Wikibase\Lib\Reporting\RethrowingExceptionHandler;

/**
 * @covers Wikibase\Lib\Reporting\RethrowingExceptionHandler
 *
 * @group Wikibase
 * @group WikibaseReporting
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class RethrowingExceptionHandlerTest extends \PHPUnit_Framework_TestCase {

	public function testReportMessage() {
		$this->setExpectedException( RuntimeException::class );

		$handler = new RethrowingExceptionHandler();
		$handler->handleException( new RuntimeException(), "test", "Just a test!" );
	}

}
