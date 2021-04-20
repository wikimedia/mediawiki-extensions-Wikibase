<?php

namespace Wikibase\Repo\Tests\Content;

use Onoi\MessageReporter\MessageReporter;
use RuntimeException;
use Wikibase\Lib\Reporting\ReportingExceptionHandler;
use Wikibase\Repo\Content\DataUpdateAdapter;
use Wikimedia\Rdbms\DBError;

/**
 * @covers \Wikibase\Repo\Content\DataUpdateAdapter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class DataUpdateAdapterTest extends \PHPUnit\Framework\TestCase {

	public function testDoUpdate() {
		$reporter = $this->createMock( MessageReporter::class );
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

	public function testDoUpdateAvoidCatchingDbErrors() {
		$reporter = $this->createMock( MessageReporter::class );
		$reporter->expects( $this->once() )
			->method( 'reportMessage' );

		$update = new DataUpdateAdapter( function() {
			throw new DBError( null, 'db error' );
		} );
		$update->setExceptionHandler( new ReportingExceptionHandler( $reporter ) );
		$this->expectException( DBError::class );

		$update->doUpdate();
	}

}
