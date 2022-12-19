<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;

/**
 * @covers \Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0-or-later
 */
class TermChangeOpSerializationValidatorTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider invalidTermSerializationProvider
	 */
	public function testGivenLanguageIsInvalid_throwsException( $serialization, $langCode, $errorCode ) {
		$validator = new TermChangeOpSerializationValidator(
			$this->getContentLanguages()
		);

		try {
			$validator->validateTermSerialization( $serialization, $langCode );
		} catch ( \Exception $exception ) {
			/** @var ChangeOpDeserializationException $exception */
			$this->assertInstanceOf( ChangeOpDeserializationException::class, $exception );
			$this->assertSame( $errorCode, $exception->getErrorCode() );
		}
	}

	/**
	 * @dataProvider validTermSerializationProvider
	 */
	public function testGivenLanguageIsValid_noExceptionIsThrown( $serialization, $langCode ) {
		$validator = new TermChangeOpSerializationValidator(
			$this->getContentLanguages()
		);
		$exception = null;

		try {
			$validator->validateTermSerialization( $serialization, $langCode );
		} catch ( \Exception $e ) {
			$exception = $e;
		}

		$this->assertNull( $exception );
	}

	public function invalidTermSerializationProvider() {
		return [
			'no language key' => [ [], 'en', 'missing-language' ],
			'language not a string (bool)' => [ [ 'language' => false ], 'en', 'not-recognized-string' ],
			'language not a string (int)' => [ [ 'language' => 3 ], 'en', 'not-recognized-string' ],
			'language not a string (null)' => [ [ 'language' => null ], 'en', 'not-recognized-string' ],
			'arg lang not matching langCode' => [
				[ 'language' => 'en', 'value' => 'foo' ],
				'de',
				'inconsistent-language',
			],
			'unknown language' => [
				[ 'language' => 'xx', 'value' => 'foo' ],
				'xx',
				'not-recognized-language',
			],
		];
	}

	public function validTermSerializationProvider() {
		return [
			'normal language code' => [
				[ 'language' => 'en', 'value' => 'foo' ],
				'en',
				false,
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
