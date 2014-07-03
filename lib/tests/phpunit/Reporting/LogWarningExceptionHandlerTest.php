<?php

namespace Wikibase\Test;

use RuntimeException;
use Wikibase\Lib\Reporting\LogWarningExceptionHandler;

/**
 * @covers Wikibase\Lib\Reporting\LogWarningExceptionHandler
 *
 * @group Wikibase
 * @group WikibaseReporting
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 */
class LogWarningExceptionHandlerTest extends \PHPUnit_Framework_TestCase {

	public function testReportMessage() {
		// LogWarningExceptionHandler uses wfLogWarning which calls trigger_error
		// which, in a PHPUnit tests, throws a  PHPUnit_Framework_Error_Warning.
		$this->setExpectedException( 'PHPUnit_Framework_Error_Warning' );

		$handler = new LogWarningExceptionHandler();
		$handler->handleException( new RuntimeException(), "test", "Just a test!" );
	}

}
