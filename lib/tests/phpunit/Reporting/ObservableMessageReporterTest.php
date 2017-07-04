<?php

namespace Wikibase\Lib\Tests\Reporting;

use Wikibase\Lib\Reporting\ObservableMessageReporter;

/**
 * @covers Wikibase\Lib\Reporting\ObservableMessageReporter
 *
 * @group Wikibase
 * @group WikibaseReporting
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class ObservableMessageReporterTest extends \PHPUnit_Framework_TestCase {

	public function testReportMessage() {
		$observedMessages = [];

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
