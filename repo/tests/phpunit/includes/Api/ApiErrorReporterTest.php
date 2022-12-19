<?php

namespace Wikibase\Repo\Tests\Api;

use ApiErrorFormatter;
use ApiMain;
use ApiUsageException;
use DataValues\IllegalValueException;
use MediaWikiIntegrationTestCase;
use Status;
use ValueParsers\ParseException;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Localizer\DispatchingExceptionLocalizer;
use Wikibase\Repo\Localizer\ExceptionLocalizer;
use Wikibase\Repo\Localizer\ParseExceptionLocalizer;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Repo\Api\ApiErrorReporter
 *
 * @group Wikibase
 * @group WikibaseValidators
 * @group WikibaseAPI
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ApiErrorReporterTest extends MediaWikiIntegrationTestCase {

	protected function assertUsageException(
		$info,
		$code,
		$httpStatusCode,
		array $expectedDataFields,
		ApiUsageException $ex
	) {
		$msg = TestingAccessWrapper::newFromObject( $ex )->getApiMessage();

		if ( $info !== null ) {
			// @todo: Change this to check the message key instead of the parsed text
			$actualInfo = ApiErrorFormatter::stripMarkup(
				$msg->inLanguage( 'en' )->useDatabase( false )->text()
			);
			$this->assertMatchesRegularExpression( $info, $actualInfo, 'error info message' );
		}

		if ( $code !== null ) {
			$this->assertSame( $code, $msg->getApiCode(), 'error code' );
		}

		if ( $httpStatusCode ) {
			$this->assertSame( $httpStatusCode, $ex->getCode(), 'HTTP status code' );
		}

		$data = $msg->getApiData();
		foreach ( $expectedDataFields as $path => $value ) {
			$path = explode( '/', $path );
			$this->assertValueAtPath( $value, $path, $data );
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
			$this->assertIsString( $data, $name );
			$this->assertMatchesRegularExpression( $expected, $data, $name );
		} else {
			$this->assertSame( $expected, $data, $name );
		}
	}

	public function exceptionProvider() {
		return [
			// Reporting an unknown / generic exception with an unknown error
			// code should result in the error code and being used directly,
			// and the error info being set to the exception message.
			'IllegalValueException' => [
				'$exception' => new IllegalValueException( 'ugh!' ),
				'$code' => 'errorreporter-test-ugh',
				'$httpStatusCode' => 0,
				'$extradata' => [],
				'$infoPattern' => '/ugh!/',
				'$expectedData' => [
				],
			],

			// Any extra data should be passed through.
			// The HTTP status code should be used.
			'extradata' => [
				'$exception' => new IllegalValueException( 'ugh!' ),
				'$code' => 'errorreporter-test-ugh',
				'$httpStatusCode' => 555,
				'$extradata' => [ 'fruit' => 'Banana' ],
				'$infoPattern' => '/ugh!/',
				'$expectedData' => [
					'fruit' => 'Banana',
				],
			],

			// Reporting an unknwon / generic exception along with a well known
			// error code should result in that code being used find a message key
			// and generate a localized message.
			'known error code' => [
				'$exception' => new IllegalValueException( 'ugh!' ),
				'$code' => 'no-such-sitelink',
				'$httpStatusCode' => 0,
				'$extradata' => [ 'fruit' => 'Banana' ],
				'$infoPattern' => '/Could not find a sitelink/',
				'$expectedData' => [
					'fruit' => 'Banana',
					'messages/0/name' => 'wikibase-api-no-such-sitelink',
					'messages/0/html' => '/gefunden/', // in German
				],
			],

			// Reporting a well known exception should result in an appropriate
			// localized message via the ExceptionLocalizer mechanism.
			'known exception' => [
				'$exception' => new ParseException( 'arg!' ),
				'$code' => 'errorreporter-test-bla',
				'$httpStatusCode' => 0,
				'$extradata' => null,
				'$infoPattern' => '/^Malformed value\./',
				'$expectedData' => [
					'messages/0/name' => 'wikibase-parse-error',
					'messages/0/html' => '/Wert/', // in German
				],
			],
		];
	}

	/**
	 * @dataProvider exceptionProvider
	 */
	public function testDieException(
		$exception,
		$code,
		$httpStatusCode,
		?array $extradata,
		$infoPattern,
		array $expectedDataFields
	) {
		$api = new ApiMain();
		$localizer = $this->getExceptionLocalizer();
		$reporter = new ApiErrorReporter( $api, $localizer, $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'de' ) );

		try {
			$reporter->dieException( $exception, $code, $httpStatusCode, $extradata );
			$this->fail( 'ApiUsageException was not thrown!' );
		} catch ( ApiUsageException $ex ) {
			$this->assertUsageException( $infoPattern, $code, $httpStatusCode, $expectedDataFields, $ex );
		}
	}

	public function messageProvider() {
		$code = 'no-such-sitelink';

		return [
			'without error code' => [
				'$code' => null,
				'$msg' => [ 'wikibase-api-no-such-sitelink', 'xywiki' ],
				'$httpRespCode' => 555,
				'$extradata' => [ 'fruit' => 'Banana' ],
				'$infoPattern' => '/sitelink/',
				'$expectedDataFields' => [
					'fruit' => 'Banana',
					'messages/0/name' => 'wikibase-api-no-such-sitelink',
					'messages/0/html' => '/gefunden/', // in German
					'messages/0/parameters/0' => '/xywiki/',
				],
			],

			// The appropriate message should be included in the extra data.
			// Most importantly, the info field should contain the message text in English,
			// while the HTML should be in German. Any Message parameters must be present.
			'known error code' => [
				'$code' => $code,
				'$msg' => [ 'wikibase-api-no-such-sitelink', 'Foo' ],
				'$httpRespCode' => 0,
				'$extradata' => [],
				'$infoPattern' => '/sitelink/',
				'$expectedDataFields' => [
					'messages/0/name' => 'wikibase-api-no-such-sitelink',
					'messages/0/html' => '/gefunden/', // in German
					'messages/0/parameters/0' => '/Foo/',
				],
			],
		];
	}

	/**
	 * @dataProvider messageProvider
	 */
	public function testDieWithError(
		$code,
		$msg,
		$httpRespCode,
		$extradata,
		$infoPattern,
		array $expectedDataFields
	) {
		$api = new ApiMain();
		$localizer = $this->getExceptionLocalizer();
		$reporter = new ApiErrorReporter( $api, $localizer, $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'de' ) );

		try {
			$reporter->dieWithError( $msg, $code, $httpRespCode, $extradata );
			$this->fail( 'ApiUsageException was not thrown!' );
		} catch ( ApiUsageException $ex ) {
			$this->assertUsageException( $infoPattern, $code, $httpRespCode, $expectedDataFields, $ex );
		}
	}

	public function statusProvider() {
		$status = Status::newFatal( 'wikibase-api-no-such-sitelink' );
		$status->fatal( 'wikibase-noentity', 'Q123' );

		return [
			// Using an (existing) message, the message should be included in the extra data.
			// The code string should be unchanged.
			// Most importantly, the info field should contain the message text in English,
			// while the HTML should be in German.
			// All error messages from the Status object should be present, all message
			// parameters must be present.
			'known error code' => [
				'$status' => $status,
				'$code' => 'errorreporter-test-ugh',
				'$infoPattern' => '/sitelink/',
				'$expectedData' => [
					'messages/0/name' => 'wikibase-api-errorreporter-test-ugh',
					'messages/1/name' => 'wikibase-api-no-such-sitelink',
					'messages/1/html' => '/gefunden/', // in German
					'messages/2/name' => 'wikibase-noentity',
					'messages/2/parameters/0' => 'Q123',
					'messages/2/html' => '/ist nicht vorhanden/', // in German
				],
			],

			// Any extra data should be passed through.
			'extradata' => [
				'$status' => $status,
				'$code' => 'errorreporter-test-ugh',
				'$infoPattern' => null,
				'$expectedData' => [],
			],
		];
	}

	/**
	 * @dataProvider statusProvider
	 */
	public function testDieStatus(
		Status $status,
		$code,
		$infoPattern,
		array $expectedDataFields
	) {
		$api = new ApiMain();
		$localizer = $this->getExceptionLocalizer();
		$reporter = new ApiErrorReporter( $api, $localizer, $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'de' ) );

		try {
			$reporter->dieStatus( $status, $code );
			$this->fail( 'ApiUsageException was not thrown!' );
		} catch ( ApiUsageException $ex ) {
			$this->assertUsageException( $infoPattern, $code, 0, $expectedDataFields, $ex );
		}
	}

	public function errorProvider() {
		return [
			// The provided description and code should be present
			// in the result.
			'IllegalValueException' => [
				'$description' => 'Ugh!',
				'$code' => 'errorreporter-test-ugh',
				'$httpStatusCode' => 0,
				'$extradata' => [],
				'$infoPattern' => '/^Ugh!$/',
				'$expectedData' => [
				],
			],

			// Any extra data should be passed through.
			// The HTTP status code should be used.
			'extradata' => [
				'$description' => 'Ugh!',
				'$code' => 'errorreporter-test-ugh',
				'$httpStatusCode' => 555,
				'$extradata' => [ 'fruit' => 'Banana' ],
				'$infoPattern' => '/^Ugh!$/',
				'$expectedData' => [
					'fruit' => 'Banana',
				],
			],
		];
	}

	/**
	 * @dataProvider errorProvider
	 */
	public function testDieError(
		$description,
		$code,
		$httpStatusCode,
		array $extradata,
		$infoPattern,
		array $expectedDataFields
	) {
		$api = new ApiMain();
		$localizer = $this->getExceptionLocalizer();
		$reporter = new ApiErrorReporter( $api, $localizer, $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'de' ) );

		try {
			$reporter->dieError( $description, $code, $httpStatusCode, $extradata );
			$this->fail( 'ApiUsageException was not thrown!' );
		} catch ( ApiUsageException $ex ) {
			$this->assertUsageException( $infoPattern, $code, $httpStatusCode, $expectedDataFields, $ex );
		}
	}

	public function warningProvider() {
		$status = Status::newGood();
		$status->warning( 'wikibase-conflict-patched' );
		$status->warning( 'undo-nochange' );

		return [
			// Messages from the Status object should be added to the 'warnings' section.
			'known error code' => [
				'$status' => $status,
				'$expectedData' => [
					'warnings/main_int/messages/0/name' => 'wikibase-conflict-patched',
					'warnings/main_int/messages/0/html' => '/Version/', // in German
					'warnings/main_int/messages/1/name' => 'undo-nochange',
					'warnings/main_int/messages/1/html' => '/Bearbeitung.*bereits/', // in German
				],
			],
		];
	}

	/**
	 * @dataProvider warningProvider
	 */
	public function testReportStatusWarnings( Status $status, array $expectedDataFields ) {
		$api = new ApiMain();
		$localizer = $this->getExceptionLocalizer();
		$reporter = new ApiErrorReporter( $api, $localizer, $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'de' ) );

		$reporter->reportStatusWarnings( $status );

		$result = $api->getResult()->getResultData();

		foreach ( $expectedDataFields as $path => $value ) {
			$path = explode( '/', $path );
			$this->assertValueAtPath( $value, $path, $result );
		}
	}

	/**
	 * @return ExceptionLocalizer
	 */
	private function getExceptionLocalizer() {
		$localizers = [
			new ParseExceptionLocalizer(),
		];

		return new DispatchingExceptionLocalizer( $localizers );
	}

}
