<?php

namespace Wikibase\Test;

use ApiMain;
use UsageException;
use Wikibase\Api\ApiErrorReporter;
use Wikibase\i18n\ExceptionLocalizer;

/**
 * @covers Wikibase\Api\ApiErrorReporter
 *
 * @group Wikibase
 * @group WikibaseValidators
 * @group WikibaseAPI
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ApiErrorReporterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return ExceptionLocalizer
	 */
	private function getMockExceptionLocalizer() {
		$mock = $this->getMock( 'Wikibase\ExceptionLocalizer' );
		return $mock;
	}

	public function testDieException( $exception, $code, $httpStatusCode, $extradata, $expectedDescription, $expectedData ) {
		$api = new ApiMain();
		$reporter = new ApiErrorReporter( $api, $this->getMockExceptionLocalizer() );

		try {
			$reporter->dieException( $exception, $code, $httpStatusCode, $extradata );
		} catch ( UsageException $ex ) {
			$this->assertUsageException( $expectedDescription, $code, $httpStatusCode, $expectedData, $ex );
		}
	}

	protected function assertUsageException( $expectedDescription, $code, $httpStatusCode, $expectedData, $ex ) {
		$this->fail( "implement me" );
	}

	public function testDieMessage() {

	}

	public function testDieStatus() {

	}

	public function testDieError() {

	}

	public function testReportStatusWarnings() {

	}

}

