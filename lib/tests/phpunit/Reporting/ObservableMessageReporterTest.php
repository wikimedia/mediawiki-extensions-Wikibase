<?php

namespace Wikibase\Test;

use Wikibase\Lib\Reporting\ObservableMessageReporter;

/**
 * @covers Wikibase\Lib\Reporting\ObservableMessageReporter
 *
 * @group Wikibase
 * @group WikibaseReporting
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ObservableMessageReporterTest extends \PHPUnit_Framework_TestCase {

	public function testReportMessage() {
		$observedMessages = array();

		$observer = function( $message ) use ( &$observedMessages ) {
			$observedMessages[] = $message;
		};

		$outerReporter = new ObservableMessageReporter();
		$innerReporter = new ObservableMessageReporter();

		$outerReporter->registerMessageReporter( $innerReporter );
		$innerReporter->registerReporterCallback( $observer );

		$innerReporter->reportMessage( "one" );
		$this->assertCount( 1, $observedMessages );

		$innerReporter->reportMessage( "two" );
		$this->assertCount( 2, $observedMessages );
	}

}
