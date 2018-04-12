<?php

namespace Wikibase\Repo\Tests\Content;

use PHPUnit4And6Compat;
use RuntimeException;
use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Reporting\ReportingExceptionHandler;
use Wikibase\Repo\Content\DataUpdateAdapter;

/**
 * @covers Wikibase\Repo\Content\DataUpdateAdapter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class DataUpdateAdapterTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function testDoUpdate() {
		$reporter = $this->getMock( MessageReporter::class );
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
