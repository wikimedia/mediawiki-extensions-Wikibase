<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use ApiUsageException;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\Localizer\ExceptionLocalizer;

/**
 * @covers Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0+
 */
class TermChangeOpSerializationValidatorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider multilangArgsProvider
	 */
	public function testValidateMultilangArgs( $arg, $langCode, $errorCode ) {
		$validator = new TermChangeOpSerializationValidator(
			$this->getContentLanguages(),
			$this->getApiErrorReporter()
		);

		$this->assertApiUsageExceptionWithCodeIsThrown(
			$errorCode,
			function() use ( $validator, $arg, $langCode ) {
				$validator->validateMultilangArgs( $arg, $langCode );
			}
		);
	}

	public function multilangArgsProvider() {
		return [
			'no language key' => [ [], 'en', 'missing-language' ],
			'language not a string (bool)' => [ [ 'language' => false ], 'en', 'not-recognized-string' ],
			'language not a string (int)' => [ [ 'language' => 3 ], 'en', 'not-recognized-string' ],
			'language not a string (null)' => [ [ 'language' => null ], 'en', 'not-recognized-string' ],
			'arg lang not matching langCode' => [
				[ 'language' => 'en', 'value' => 'foo' ],
				'de',
				'inconsistent-language'
			],
			'unknown language' => [
				[ 'language' => 'xx', 'value' => 'foo' ],
				'xx',
				'not-recognized-language'
			],
			'valid' => [
				[ 'language' => 'en', 'value' => 'foo' ],
				'en',
				false
			],
			'valid numeric langcode' => [
				[ 'language' => 'en', 'value' => 'foo' ],
				'123',
				false
			],
			'valid int langcode' => [
				[ 'language' => 'en', 'value' => 'foo' ],
				123,
				false
			],
			'valid remove' => [
				[ 'language' => 'en', 'remove' => '' ],
				'en',
				false
			],
		];
	}

	private function getContentLanguages() {
		return new StaticContentLanguages( [ 'en', 'de', 'fr' ] );
	}

	private function getApiErrorReporter() {
		return new ApiErrorReporter(
			new \ApiMain(),
			$this->getMock( ExceptionLocalizer::class ),
			new \Language()
		);
	}

	public function assertApiUsageExceptionWithCodeIsThrown( $expectedCode, callable $callback ) {
		/** @var ApiUsageException $exception */
		$exception = null;
		try {
			$callback();
		} catch ( \Exception $e ) {
			$exception = $e;
		}

		if ( $expectedCode === false ) {
			$this->assertNull( $exception );
		} else {
			$this->assertInstanceOf( ApiUsageException::class, $exception );
			$this->assertSame( $expectedCode, $exception->getCodeString() );
		}
	}

}
