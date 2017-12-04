<?php

namespace Wikibase\Repo\Tests\Api;

use ApiMain;
use Wikibase\Lib\DataTypeFactory;
use DataValues\Geo\Parsers\GlobeCoordinateParser;
use FauxRequest;
use Language;
use ApiUsageException;
use ValueParsers\NullParser;
use ValueParsers\ParseException;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Api\ParseValue;
use Wikibase\Repo\BuilderBasedDataTypeValidatorFactory;
use Wikibase\Repo\Validators\DataValueValidator;
use Wikibase\Repo\Validators\RegexValidator;
use Wikibase\Repo\ValueParserFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Api\ParseValue
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class ParseValueTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @param string[] $params
	 *
	 * @return ParseValue
	 */
	private function newApiModule( array $params ) {

		$request = new FauxRequest( $params, true );
		$main = new ApiMain( $request );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$exceptionLocalizer = $wikibaseRepo->getExceptionLocalizer();
		$validatorErrorLocalizer = $wikibaseRepo->getValidatorErrorLocalizer();

		$errorReporter = new ApiErrorReporter(
			$main,
			$exceptionLocalizer,
			Language::factory( 'qqq' )
		);

		$dataTypeFactory = new DataTypeFactory( [
			'string' => 'string',
			'url' => 'string',
			'globe-coordinate' => 'globecoordinate',
		] );

		$valueParserFactory = new ValueParserFactory( [
			'null' => [ $this, 'newNullParser' ],
			'string' => [ $this, 'newNullParser' ],
			'url' => [ $this, 'newNullParser' ],
			'globe-coordinate' => [ $this, 'newGlobeCoordinateParser' ],
		] );

		$validatorFactory = new BuilderBasedDataTypeValidatorFactory( [
			'string' => [ $this, 'newArrayWithStringValidator' ],
			'url' => [ $this, 'newArrayWithStringValidator' ],
		] );

		return new ParseValue(
			$main,
			'wbparsevalue',
			$dataTypeFactory,
			$valueParserFactory,
			$validatorFactory,
			$exceptionLocalizer,
			$validatorErrorLocalizer,
			$errorReporter
		);
	}

	public function newArrayWithStringValidator() {
		return [
			new DataValueValidator(
				new RegexValidator( '/INVALID/', true, 'no-kittens' )
			) ];
	}

	public function newNullParser() {
		return new NullParser();
	}

	public function newGlobeCoordinateParser() {
		return new GlobeCoordinateParser();
	}

	private function callApiModule( array $params ) {
		$module = $this->newApiModule( $params );

		$module->execute();
		$result = $module->getResult();

		$data = $result->getResultData( null, [
			'BC' => [ 'nobool' ],
			'Types' => [],
			'Strip' => 'all',
		] );
		return $data;
	}

	/**
	 * @return array[]
	 */
	public function provideValid() {
		return [
			'datatype=string' => [
				[
					'values' => 'foo',
					'datatype' => 'string',
				],
				[
					'0/raw' => 'foo',
					'0/type' => 'unknown',
					'0/value' => 'foo',
				],
			],

			'datatype=url' => [
				[
					'values' => 'foo',
					'datatype' => 'string',
				],
				[
					'0/raw' => 'foo',
					'0/type' => 'unknown',
					'0/value' => 'foo',
				],
			],

			'validation' => [
				[
					'values' => 'VALID',
					'datatype' => 'string',
					'validate' => ''
				],
				[
					'0/raw' => 'VALID',
					'0/valid' => true,
				],
			],

			'bad value, validation failure' => [
				[
					'values' => 'INVALID',
					'datatype' => 'string',
					'validate' => ''
				],
				[
					'0/raw' => 'INVALID',
					'0/valid' => false,
					'0/error' => 'ValidationError',
					'0/messages/0/name' => 'wikibase-validator-no-kittens',
					'0/messages/0/html/*' => '/.+/',
					'0/validation-errors/0' => 'no-kittens',
				],
			],

			'bad value, no validation' => [
				[
					'values' => 'INVALID',
					'datatype' => 'string',
				],
				[
					'0/raw' => 'INVALID',
					'0/type' => 'unknown',
				],
			],

			'parser=string (deprecated param)' => [
				[
					'values' => 'foo',
					'parser' => 'string',
				],
				[
					'0/raw' => 'foo',
					'0/type' => 'unknown',
					'0/value' => 'foo',
				],
			],

			'values=foo|bar' => [
				[
					'values' => 'foo|bar',
					'datatype' => 'string',
				],
				[
					'0/raw' => 'foo',
					'0/type' => 'unknown',
					'0/value' => 'foo',

					'1/raw' => 'bar',
					'1/type' => 'unknown',
					'1/value' => 'bar',
				],
			],

			'datatype=globe-coordinate' => [
				[
					'values' => '5.5S,37W',
					'datatype' => 'globe-coordinate',
				],
				[
					'0/raw' => '5.5S,37W',
					'0/type' => 'globecoordinate',
				],
			],

			'malformed coordinate' => [
				[
					'values' => 'XYZ',
					'datatype' => 'globe-coordinate',
				],
				[
					'0/raw' => 'XYZ',
					'0/error' => ParseException::class,
					'0/error-info' => '/^.+$/',
					'0/messages/0/html/*' => '/^.+$/',
				],
			],

			'good and bad' => [
				[
					'values' => 'XYZ|5.5S,37W',
					'datatype' => 'globe-coordinate',
				],
				[
					'0/error' => ParseException::class,
					'1/type' => 'globecoordinate',
				],
			],

		];
	}

	protected function assertValueAtPath( $expected, $path, $data ) {
		$name = '';
		foreach ( $path as $step ) {
			$name .= '/' . $step;
			$this->assertInternalType( 'array', $data, $name );
			$this->assertArrayHasKey( $step, $data, $name );
			$data = $data[$step];
		}

		if ( is_string( $expected ) && preg_match( '/^([^\s\w\d]).*\1[a-zA-Z]*$/', $expected ) ) {
			$this->assertInternalType( 'string', $data, $name );
			$this->assertRegExp( $expected, $data, $name );
		} else {
			$this->assertEquals( $expected, $data, $name );
		}
	}

	/**
	 * @dataProvider provideValid
	 */
	public function testParse( array $params, array $expected ) {

		$result = $this->callApiModule( $params );

		$this->assertArrayHasKey( 'results', $result );

		foreach ( $expected as $path => $value ) {
			$path = explode( '/', $path );
			$this->assertValueAtPath( $value, $path, $result['results'] );
		}
	}

	/**
	 * @return array[]
	 */
	public function provideInvalid() {
		return [
			'no datatype' => [
				[
					'values' => 'foo',
				]
			],
			'bad datatype (valid parser name)' => [
				[
					'values' => 'foo',
					'datatype' => 'null',
				]
			],
			'bad parser' => [
				[
					'values' => 'foo',
					'parser' => 'foo',
				]
			],
		];
	}

	/**
	 * @dataProvider provideInvalid
	 */
	public function testParse_failure( array $params ) {
		$this->setExpectedException( ApiUsageException::class );
		$this->callApiModule( $params );
	}

}
