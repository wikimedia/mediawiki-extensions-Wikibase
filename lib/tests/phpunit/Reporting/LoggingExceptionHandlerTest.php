<?php

namespace Wikibase\Test;

use RuntimeException;
use Wikibase\Lib\Reporting\LoggingExceptionHandler;

/**
 * @covers Wikibase\Lib\Reporting\LoggingExceptionHandler
 *
 * @group Wikibase
 * @group WikibaseReporting
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 */
class LoggingExceptionHandlerTest extends \PHPUnit_Framework_TestCase {

	public function testReportMessage() {
		// LoggingExceptionHandler uses wfLogWarning which calls trigger_error
		// which, in a PHPUnit tests, throws a  PHPUnit_Framework_Error_Warning.
		$this->setExpectedException( 'PHPUnit_Framework_Error_Warning' );

		$handler = new LoggingExceptionHandler();
		$handler->handleException( new RuntimeException(), "test", "Just a test!" );
	}

}
