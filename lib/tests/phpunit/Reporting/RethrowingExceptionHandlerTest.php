<?php

namespace Wikibase\Lib\Tests\Reporting;

use RuntimeException;
use Wikibase\Lib\Reporting\RethrowingExceptionHandler;

/**
 * @covers \Wikibase\Lib\Reporting\RethrowingExceptionHandler
 *
 * @group Wikibase
 * @group WikibaseReporting
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class RethrowingExceptionHandlerTest extends \PHPUnit\Framework\TestCase {

	public function testReportMessage() {
		$this->expectException( RuntimeException::class );

		$handler = new RethrowingExceptionHandler();
		$handler->handleException( new RuntimeException(), "test", "Just a test!" );
	}

}
