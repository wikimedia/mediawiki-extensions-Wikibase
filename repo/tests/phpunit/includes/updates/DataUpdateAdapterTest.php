<?php

namespace Wikibase\Updates\Test;

use RuntimeException;
use Wikibase\Lib\Reporting\ReportingExceptionHandler;
use Wikibase\Updates\DataUpdateAdapter;

/**
 * @covers Wikibase\Updates\DataUpdateAdapter
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DataUpdateAdapterTest extends \PHPUnit_Framework_TestCase {

	public function testDoUpdate() {
		$reporter = $this->getMock( 'Wikibase\Lib\Reporting\MessageReporter' );
		$reporter->expects( $this->once() )
			->method( 'reportMessage' );

		$update = new DataUpdateAdapter( function() {
			throw new RuntimeException( 'Test' );
		} );
		$update->setExceptionHandler( new ReportingExceptionHandler( $reporter ) );

		// Should call the callback provided to the constructor, which will throw an exception,
		// which is then reported to $reporter via the ExceptionHandler.
		$update->doUpdate();
	}

}
