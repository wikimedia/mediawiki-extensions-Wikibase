<?php

namespace Wikibase\Lib\Tests\Reporting;

use PHPUnit4And6Compat;
use RuntimeException;
use Wikibase\Lib\Reporting\RethrowingExceptionHandler;

/**
 * @covers Wikibase\Lib\Reporting\RethrowingExceptionHandler
 *
 * @group Wikibase
 * @group WikibaseReporting
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class RethrowingExceptionHandlerTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function testReportMessage() {
		$this->setExpectedException( RuntimeException::class );

		$handler = new RethrowingExceptionHandler();
		$handler->handleException( new RuntimeException(), "test", "Just a test!" );
	}

}
