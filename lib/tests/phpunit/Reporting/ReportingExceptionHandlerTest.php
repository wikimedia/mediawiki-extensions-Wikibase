<?php

namespace Wikibase\Lib\Tests\Reporting;

use PHPUnit4And6Compat;
use RuntimeException;
use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Reporting\ReportingExceptionHandler;

/**
 * @covers Wikibase\Lib\Reporting\ReportingExceptionHandler
 *
 * @group Wikibase
 * @group WikibaseReporting
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ReportingExceptionHandlerTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function testReportMessage() {
		$reporter = $this->getMock( MessageReporter::class );
		$reporter->expects( $this->once() )
			->method( 'reportMessage' );

		/** @var MessageReporter $reporter */
		$handler = new ReportingExceptionHandler( $reporter );
		$handler->handleException( new RuntimeException(), "test", "Just a test!" );
	}

}
