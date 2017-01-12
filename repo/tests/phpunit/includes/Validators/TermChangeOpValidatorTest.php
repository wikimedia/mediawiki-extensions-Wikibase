<?php

namespace Wikibase\Repo\Tests\Validators;

use InvalidArgumentException;
use RuntimeException;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\Api\ApiErrorReporter;

/**
 * @covers Wikibase\Repo\Validators\NumberValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0+
 */
class TermChangeOpValidatorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider multilangArgsProvider
	 */
	public function testValidateMultilangArgs( $arg, $langCode, $errorCode ) {
		$validator = new TermChangeOpValidator(
			$this->getApiErrorReporter( $errorCode ),
			$this->getContentLanguages()
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
			'valid remove' => [
				[ 'language' => 'en', 'remove' => true ],
				'en',
				false
			],
		];
	}

	private function getContentLanguages() {
		return new StaticContentLanguages( [ 'en', 'de', 'fr' ] );
	}

	/**
	 * TODO: Refactor to mock class or into test helper
	 *
	 * @param bool $expectsError
	 *
	 * @return ApiErrorReporter
	 */
	private function getApiErrorReporter( $expectsError = false ) {
		$errorReporter = $this->getMockBuilder( ApiErrorReporter::class )
			->disableOriginalConstructor()
			->getMock();

		if ( !$expectsError ) {
			$errorReporter->expects( $this->never() )
				->method( 'dieError' );
		} else {
			$errorReporter->expects( $this->once() )
				->method( 'dieError' )
				->willReturnCallback( function( $description, $errorCode ) {
					throw new RuntimeException( $errorCode );
				} );
		}

		return $errorReporter;
	}

}
