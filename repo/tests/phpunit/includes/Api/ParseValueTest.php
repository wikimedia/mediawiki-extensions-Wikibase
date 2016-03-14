<?php

namespace Wikibase\Test\Repo\Api;

use ApiMain;
use DataTypes\DataTypeFactory;
use DataValues\Geo\Parsers\GlobeCoordinateParser;
use FauxRequest;
use Language;
use UsageException;
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
 * @group WikibaseRepo
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

		$module = new ParseValue( $main, 'wbparsevalue' );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$exceptionLocalizer = $wikibaseRepo->getExceptionLocalizer();
		$validatorErrorLocalizer = $wikibaseRepo->getValidatorErrorLocalizer();

		$errorReporter = new ApiErrorReporter(
			$module,
			$exceptionLocalizer,
			Language::factory( 'qqq' )
		);

		$dataTypeFactory = new DataTypeFactory( array(
			'string' => 'string',
			'url' => 'string',
			'globe-coordinate' => 'globecoordinate',
		) );

		$valueParserFactory = new ValueParserFactory( array(
			'null' => array( $this, 'newNullParser' ),
			'string' => array( $this, 'newNullParser' ),
			'url' => array( $this, 'newNullParser' ),
			'globe-coordinate' => array( $this, 'newGlobeCoordinateParser' ),
		) );

		$validatorFactory = new BuilderBasedDataTypeValidatorFactory( array(
			'string' => array( $this, 'newArrayWithStringValidator' ),
			'url' => array( $this, 'newArrayWithStringValidator' ),
		) );

		$module->setServices(
			$dataTypeFactory,
			$valueParserFactory,
			$validatorFactory,
			$exceptionLocalizer,
			$validatorErrorLocalizer,
			$errorReporter
		);

		return $module;
	}

	public function newArrayWithStringValidator() {
		return array(
			new DataValueValidator(
				new RegexValidator( '/INVALID/', true, 'no-kittens' )
			) );
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

		$data = $result->getResultData( null, array(
			'BC' => array( 'nobool' ),
			'Types' => array(),
			'Strip' => 'all',
		) );
		return $data;
	}

	/**
	 * @return array[]
	 */
	public function provideValid() {
		return array(
			'datatype=string' => array(
				array(
					'values' => 'foo',
					'datatype' => 'string',
				),
				array(
					'0/raw' => 'foo',
					'0/type' => 'unknown',
					'0/value' => 'foo',
				),
			),

			'datatype=url' => array(
				array(
					'values' => 'foo',
					'datatype' => 'string',
				),
				array(
					'0/raw' => 'foo',
					'0/type' => 'unknown',
					'0/value' => 'foo',
				),
			),

			'validation' => array(
				array(
					'values' => 'VALID',
					'datatype' => 'string',
					'validate' => ''
				),
				array(
					'0/raw' => 'VALID',
					'0/valid' => true,
				),
			),

			'bad value, validation failure' => array(
				array(
					'values' => 'INVALID',
					'datatype' => 'string',
					'validate' => ''
				),
				array(
					'0/raw' => 'INVALID',
					'0/valid' => false,
					'0/error' => 'ValidationError',
					'0/messages/0/name' => 'wikibase-validator-no-kittens',
					'0/messages/0/html/*' => '/.+/',
					'0/validation-errors/0' => 'no-kittens',
				),
			),

			'bad value, no validation' => array(
				array(
					'values' => 'INVALID',
					'datatype' => 'string',
				),
				array(
					'0/raw' => 'INVALID',
					'0/type' => 'unknown',
				),
			),

			'parser=string (deprecated param)' => array(
				array(
					'values' => 'foo',
					'parser' => 'string',
				),
				array(
					'0/raw' => 'foo',
					'0/type' => 'unknown',
					'0/value' => 'foo',
				),
			),

			'values=foo|bar' => array(
				array(
					'values' => 'foo|bar',
					'datatype' => 'string',
				),
				array(
					'0/raw' => 'foo',
					'0/type' => 'unknown',
					'0/value' => 'foo',

					'1/raw' => 'bar',
					'1/type' => 'unknown',
					'1/value' => 'bar',
				),
			),

			'datatype=globe-coordinate' => array(
				array(
					'values' => '5.5S,37W',
					'datatype' => 'globe-coordinate',
				),
				array(
					'0/raw' => '5.5S,37W',
					'0/type' => 'globecoordinate',
				),
			),

			'malformed coordinate' => array(
				array(
					'values' => 'XYZ',
					'datatype' => 'globe-coordinate',
				),
				array(
					'0/raw' => 'XYZ',
					'0/error' => ParseException::class,
					'0/error-info' => '/^.+$/',
					'0/messages/0/html/*' => '/^.+$/',
				),
			),

			'good and bad' => array(
				array(
					'values' => 'XYZ|5.5S,37W',
					'datatype' => 'globe-coordinate',
				),
				array(
					'0/error' => ParseException::class,
					'1/type' => 'globecoordinate',
				),
			),

		);
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
		return array(
			'no datatype' => array(
				array(
					'values' => 'foo',
				)
			),
			'bad datatype (valid parser name)' => array(
				array(
					'values' => 'foo',
					'datatype' => 'null',
				)
			),
			'bad parser' => array(
				array(
					'values' => 'foo',
					'parser' => 'foo',
				)
			),
		);
	}

	/**
	 * @dataProvider provideInvalid
	 */
	public function testParse_failure( array $params ) {
		$this->setExpectedException( UsageException::class );
		$this->callApiModule( $params );
	}

}
