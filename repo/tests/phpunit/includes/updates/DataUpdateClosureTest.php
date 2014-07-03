<?php

namespace Wikibase\Updates\Test;

use RuntimeException;
use Wikibase\Lib\Reporting\ReportingExceptionHandler;
use Wikibase\Updates\DataUpdateClosure;

/**
 * @covers Wikibase\Updates\DataUpdateClosure
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DataUpdateClosureTest extends \PHPUnit_Framework_TestCase {

	public function testDoUpdate() {
		$reporter = $this->getMock( 'Wikibase\Lib\Reporting\MessageReporter' );
		$reporter->expects( $this->once() )
			->method( 'reportMessage' );

		$update = new DataUpdateClosure( function() { throw new RuntimeException( 'Test' ); } );
		$update->setExceptionHandler( new ReportingExceptionHandler( $reporter ) );

		// Should call the callback provided to the constructor, which will throw an exception,
		// which is then reported to $reporter via the ExceptionHandler.
		$update->doUpdate();
	}

}
