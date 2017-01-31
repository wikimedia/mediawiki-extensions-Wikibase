<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use ApiUsageException;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\Localizer\ExceptionLocalizer;

/**
 * @covers Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class TermChangeOpSerializationValidatorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider invalidMultilangArgsProvider
	 */
	public function testGivenLanguageIsInvalid_throwsException( $arg, $langCode, $errorCode ) {
		$validator = new TermChangeOpSerializationValidator(
			$this->getContentLanguages()
		);

		try {
			$validator->validateMultilangArgs( $arg, $langCode );
		} catch ( \Exception $exception ) {
			/** @var \Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException $exception */
			$this->assertInstanceOf( \Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException::class, $exception );
			$this->assertSame( $errorCode, $exception->getErrorCode() );
		}
	}

	/**
	 * @dataProvider validMultilangArgsProvider
	 */
	public function testGivenLanguageIsValid_noExceptionIsThrown( $arg, $langCode ) {
		$validator = new \Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator(
			$this->getContentLanguages()
		);
		$exception = null;

		try {
			$validator->validateMultilangArgs( $arg, $langCode );
		} catch ( \Exception $e ) {
			$exception = $e;
		}

		$this->assertNull( $exception );
	}

	public function invalidMultilangArgsProvider() {
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
		];
	}

	public function validMultiLangArgsProvider() {
		return [
			'normal language code' => [
				[ 'language' => 'en', 'value' => 'foo' ],
				'en',
				false
			],
			'numeric langcode' => [
				[ 'language' => 'en', 'value' => 'foo' ],
				'123',
			],
			'int langcode' => [
				[ 'language' => 'en', 'value' => 'foo' ],
				123,
			],
			'remove' => [
				[ 'language' => 'en', 'remove' => '' ],
				'en',
			],
		];
	}

	private function getContentLanguages() {
		return new StaticContentLanguages( [ 'en', 'de', 'fr' ] );
	}

}
