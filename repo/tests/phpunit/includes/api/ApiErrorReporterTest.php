<?php

namespace Wikibase\Test;

use ApiMain;
use DataValues\IllegalValueException;
use Language;
use Message;
use Status;
use UsageException;
use ValueParsers\ParseException;
use Wikibase\Api\ApiErrorReporter;
use Wikibase\Lib\Localizer\DispatchingExceptionLocalizer;
use Wikibase\Lib\Localizer\ExceptionLocalizer;
use Wikibase\Lib\Localizer\ParseExceptionLocalizer;

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
class ApiErrorReporterTest extends \MediaWikiTestCase {

	protected function assertUsageException( $info, $code, $httpStatusCode, $expectedDataFields, UsageException $ex ) {
		$messageArray = $ex->getMessageArray();

		$this->assertArrayHasKey( 'code', $messageArray );
		$this->assertArrayHasKey( 'info', $messageArray );

		if ( $info !== null ) {
			$this->assertRegExp( $info, $messageArray['info'] );
		}

		if ( $code !== null ) {
			$this->assertEquals( $code, $messageArray['code'] );
		}

		if ( $httpStatusCode ) {
			$this->assertEquals( $httpStatusCode, $ex->getCode() );
		}

		if ( $expectedDataFields ) {
			foreach ( $expectedDataFields as $path => $value ) {
				$path = explode( '/', $path );
				$this->assertValueAtPath( $value, $path, $messageArray );
			}
		}
	}

	protected function assertValueAtPath( $expected, $path, $data ) {
		$name = '';
		foreach ( $path as $step ) {
			$this->assertArrayHasKey( $step, $data );
			$data = $data[$step];
			$name .= '/' . $step;
		}

		if ( preg_match( '/^([^\s\w\d]).*\1[a-zA-Z]*$/', $expected ) ) {
			$this->assertInternalType( 'string', $data, $name );
			$this->assertRegExp( $expected, $data, $name );
		} else {
			$this->assertEquals( $expected, $data, $name );
		}
	}

	public function exceptionProvider() {
		return array(
			// Reporting an unknown / generic exception with an unknown error
			// code should result in the error code and being used directly,
			// and the error info being set to the exception message.
			'IllegalValueException' => array(
				'$exception' => new IllegalValueException( 'ugh!' ),
				'$code' => 'errorreporter-test-ugh',
				'$httpStatusCode' => 0,
				'$extradata' => array(),
				'$infoPattern' => '/ugh!/',
				'$expectedData' => array(
					'code' => 'errorreporter-test-ugh',
				),
			),

			// Any extra data should be passed through.
			// The HTTP status code should be used.
			'extradata' => array(
				'$exception' => new IllegalValueException( 'ugh!' ),
				'$code' => 'errorreporter-test-ugh',
				'$httpStatusCode' => 555,
				'$extradata' => array( 'fruit' => 'Banana' ),
				'$infoPattern' => '/ugh!/',
				'$expectedData' => array(
					'fruit' => 'Banana',
				),
			),

			// Reporting an unknwon / generic exception along with a well known
			// error code should result in that code being used find a message key
			// and generate a localized message.
			'known error code' => array(
				'$exception' => new IllegalValueException( 'ugh!' ),
				'$code' => 'no-such-sitelink',
				'$httpStatusCode' => 0,
				'$extradata' => array( 'fruit' => 'Banana' ),
				'$infoPattern' => '/sitelink.*\(ugh!\)$/',
				'$expectedData' => array(
					'fruit' => 'Banana',
					'messages/0/name' => 'wikibase-api-no-such-sitelink',
					'messages/0/html/*' => '/gefunden/', // in German
				),
			),

			// Reporting a well known exception should result in an appropriate
			// localized message via the ExceptionLocalizer mechanism.
			'known exception' => array(
				'$exception' => new ParseException( 'arg!' ),
				'$code' => 'errorreporter-test-bla',
				'$httpStatusCode' => 0,
				'$extradata' => null,
				'$infoPattern' => '/^Malformed value\./',
				'$expectedData' => array(
					'messages/0/name' => 'wikibase-parse-error',
					'messages/0/html/*' => '/Wert/', // in German
				),
			),
		);
	}

	/**
	 * @dataProvider exceptionProvider
	 */
	public function testDieException( $exception, $code, $httpStatusCode, $extradata, $infoPattern, $expectedDataFields ) {
		$api = new ApiMain();
		$localizer = $this->getExceptionLocalizer();
		$reporter = new ApiErrorReporter( $api, $localizer, Language::factory( 'de' ) );

		try {
			$reporter->dieException( $exception, $code, $httpStatusCode, $extradata );
			$this->fail( 'UsageException was not thrown!' );
		} catch ( UsageException $ex ) {
			$this->assertUsageException( $infoPattern, $code, $httpStatusCode, $expectedDataFields, $ex );
		}
	}

	public function messageProvider() {
		$message = new Message( 'wikibase-api-no-such-sitelink', array( 'Foo' ) );

		return array(
			// Using an (existing) message, the message should be included in the extra data.
			// The code string should be unchanged.
			// Most importantly, the info field should contain the message text in English,
			// while the HTML should be in German. Any Message parameters must be present.
			'known error code' => array(
				'$message' => $message,
				'$code' => 'errorreporter-test-ugh',
				'$httpStatusCode' => 0,
				'$extradata' => null,
				'$infoPattern' => '/sitelink/',
				'$expectedData' => array(
					'messages/0/name' => 'wikibase-api-no-such-sitelink',
					'messages/0/html/*' => '/gefunden/', // in German
					'messages/0/parameters/0' => '/Foo/',
				),
			),

			// Any extra data should be passed through.
			// The HTTP status code should be used.
			'extradata' => array(
				'$message' => $message,
				'$code' => 'errorreporter-test-ugh',
				'$httpStatusCode' => 555,
				'$extradata' => array( 'fruit' => 'Banana' ),
				'$infoPattern' => null,
				'$expectedData' => array(
					'fruit' => 'Banana',
				),
			),
		);
	}

	/**
	 * @dataProvider messageProvider
	 */
	public function testDieMessage( Message $message, $code, $httpStatusCode, $extradata, $infoPattern, $expectedDataFields ) {
		$api = new ApiMain();
		$localizer = $this->getExceptionLocalizer();
		$reporter = new ApiErrorReporter( $api, $localizer, Language::factory( 'de' ) );

		try {
			$reporter->dieMessage( $message, $code, $httpStatusCode, $extradata );
			$this->fail( 'UsageException was not thrown!' );
		} catch ( UsageException $ex ) {
			$this->assertUsageException( $infoPattern, $code, $httpStatusCode, $expectedDataFields, $ex );
		}
	}

	public function statusProvider() {
		$status = Status::newFatal( 'wikibase-api-no-such-sitelink' );
		$status->fatal( 'wikibase-noentity', 'Q123' );

		return array(
			// Using an (existing) message, the message should be included in the extra data.
			// The code string should be unchanged.
			// Most importantly, the info field should contain the message text in English,
			// while the HTML should be in German.
			// All error messages from the Status object should be present, all message
			// parameters must be present.
			'known error code' => array(
				'$status' => $status,
				'$code' => 'errorreporter-test-ugh',
				'$httpStatusCode' => 0,
				'$extradata' => null,
				'$infoPattern' => '/sitelink/',
				'$expectedData' => array(
					'messages/0/name' => 'wikibase-api-no-such-sitelink',
					'messages/0/html/*' => '/gefunden/', // in German
					'messages/1/name' => 'wikibase-noentity',
					'messages/1/parameters/0' => 'Q123',
					'messages/1/html/*' => '/Datensatz/', // in German
				),
			),

			// Any extra data should be passed through.
			// The HTTP status code should be used.
			'extradata' => array(
				'$status' => $status,
				'$code' => 'errorreporter-test-ugh',
				'$httpStatusCode' => 555,
				'$extradata' => array( 'fruit' => 'Banana' ),
				'$infoPattern' => null,
				'$expectedData' => array(
					'fruit' => 'Banana',
				),
			),
		);
	}

	/**
	 * @dataProvider statusProvider
	 */
	public function testDieStatus( Status $status, $code, $httpStatusCode, $extradata, $infoPattern, $expectedDataFields ) {
		$api = new ApiMain();
		$localizer = $this->getExceptionLocalizer();
		$reporter = new ApiErrorReporter( $api, $localizer, Language::factory( 'de' ) );

		try {
			$reporter->dieStatus( $status, $code, $httpStatusCode, $extradata );
			$this->fail( 'UsageException was not thrown!' );
		} catch ( UsageException $ex ) {
			$this->assertUsageException( $infoPattern, $code, $httpStatusCode, $expectedDataFields, $ex );
		}
	}

	public function errorProvider() {
		return array(
			// The provided description and code should be present
			// in the result.
			'IllegalValueException' => array(
				'$description' => 'Ugh!',
				'$code' => 'errorreporter-test-ugh',
				'$httpStatusCode' => 0,
				'$extradata' => array(),
				'$infoPattern' => '/^Ugh!$/',
				'$expectedData' => array(
					'info' => 'Ugh!',
					'code' => 'errorreporter-test-ugh',
				),
			),

			// Any extra data should be passed through.
			// The HTTP status code should be used.
			'extradata' => array(
				'$description' => 'Ugh!',
				'$code' => 'errorreporter-test-ugh',
				'$httpStatusCode' => 555,
				'$extradata' => array( 'fruit' => 'Banana' ),
				'$infoPattern' => '/^Ugh!$/',
				'$expectedData' => array(
					'fruit' => 'Banana',
				),
			),
		);
	}

	/**
	 * @dataProvider errorProvider
	 */
	public function testDieError( $description, $code, $httpStatusCode, $extradata, $infoPattern, $expectedDataFields ) {
		$api = new ApiMain();
		$localizer = $this->getExceptionLocalizer();
		$reporter = new ApiErrorReporter( $api, $localizer, Language::factory( 'de' ) );

		try {
			$reporter->dieError( $description, $code, $httpStatusCode, $extradata );
			$this->fail( 'UsageException was not thrown!' );
		} catch ( UsageException $ex ) {
			$this->assertUsageException( $infoPattern, $code, $httpStatusCode, $expectedDataFields, $ex );
		}
	}

	public function warningProvider() {
		$status = Status::newGood();
		$status->warning( 'wikibase-conflict-patched' );
		$status->warning( 'undo-nochange' );

		return array(
			// Messages from the Status object should be added to the 'warnings' section.
			'known error code' => array(
				'$status' => $status,
				'$expectedData' => array(
					'warnings/main_int/messages/0/name' => 'wikibase-conflict-patched',
					'warnings/main_int/messages/0/html/*' => '/Version/', // in German
					'warnings/main_int/messages/1/name' => 'undo-nochange',
					'warnings/main_int/messages/1/html/*' => '/Bearbeitung.*bereits/', // in German
				),
			),
		);
	}

	/**
	 * @dataProvider warningProvider
	 */
	public function testReportStatusWarnings( Status $status, $expectedDataFields ) {
		$api = new ApiMain();
		$localizer = $this->getExceptionLocalizer();
		$reporter = new ApiErrorReporter( $api, $localizer, Language::factory( 'de' ) );

		$reporter->reportStatusWarnings( $status );

		$result = $api->getResult()->getData();

		foreach ( $expectedDataFields as $path => $value ) {
			$path = explode( '/', $path );
			$this->assertValueAtPath( $value, $path, $result );
		}
	}

	/**
	 * @return ExceptionLocalizer
	 */
	private function getExceptionLocalizer() {
		$localizers = array(
			new ParseExceptionLocalizer()
		);

		return new DispatchingExceptionLocalizer( $localizers );
	}

}
