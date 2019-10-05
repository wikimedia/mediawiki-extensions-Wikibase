<?php

namespace Wikibase\Lib\Tests\Reporting;

use Onoi\MessageReporter\MessageReporter;
use RuntimeException;
use Wikibase\Lib\Reporting\ReportingExceptionHandler;

/**
 * @covers \Wikibase\Lib\Reporting\ReportingExceptionHandler
 *
 * @group Wikibase
 * @group WikibaseReporting
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ReportingExceptionHandlerTest extends \PHPUnit\Framework\TestCase {

	public function testReportMessage() {
		$reporter = $this->createMock( MessageReporter::class );
		$reporter->expects( $this->once() )
			->method( 'reportMessage' );

		/** @var MessageReporter $reporter */
		$handler = new ReportingExceptionHandler( $reporter );
		$handler->handleException( new RuntimeException(), "test", "Just a test!" );
	}

}
