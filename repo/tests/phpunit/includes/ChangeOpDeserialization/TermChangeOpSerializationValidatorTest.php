<?php

namespace Wikibase\Repo\Tests\ChangeOpDeserialization;

use RuntimeException;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\ChangeOpDeserialization\TermChangeOpSerializationValidator;

/**
 * @covers Wikibase\Repo\ChangeOpDeserialization\TermChangeOpSerializationValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0+
 */
class TermChangeOpSerializationValidatorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var ChangeOpDeserializationTestHelper
	 */
	private $testHelper;

	public function setUp() {
		$this->testHelper = new ChangeOpDeserializationTestHelper( $this );
	}

	/**
	 * @dataProvider multilangArgsProvider
	 */
	public function testValidateMultilangArgs( $arg, $langCode, $errorCode ) {
		$validator = new TermChangeOpSerializationValidator(
			$this->getContentLanguages(),
			$this->testHelper->getApiErrorReporter( $errorCode )
		);

		if ( $errorCode !== false ) {
			$this->setExpectedException( RuntimeException::class, $errorCode );
		}

		$validator->validateMultilangArgs( $arg, $langCode );
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

}
