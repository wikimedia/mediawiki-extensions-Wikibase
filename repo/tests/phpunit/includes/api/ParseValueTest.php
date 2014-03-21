<?php

namespace Wikibase\Test\Api;
use DataValues\DataValue;
use DataValues\NumberValue;
use DataValues\StringValue;
use UsageException;

/**
 * @covers Wikibase\Api\ParseValues
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 *
 * @group Database
 * @group medium
 */
class ParseValuesTest extends WikibaseApiTestCase {

	public static function provideParseValues() {
		return array(
			'parse string value' => array(
				'p' => array( 'parser' => 'null', 'values' => 'Hello', 'options' => '' ),
				'e' => array(
					array( 'raw' => 'Hello', 'type' => 'unknown', 'value' => 'Hello' )
				) ),
			'parse multiple string values' => array(
				'p' => array( 'parser' => 'null', 'values' => 'Hello|World', 'options' => '' ),
				'e' => array(
					array( 'raw' => 'Hello', 'type' => 'unknown', 'value' => 'Hello' ),
					array( 'raw' => 'World', 'type' => 'unknown', 'value' => 'World' )
				) ),
			'parse multiple integers' => array(
				'p' => array( 'parser' => 'int', 'values' => '8|-17', 'options' => '' ),
				'e' => array( new NumberValue( 8 ), new NumberValue( -17 ) ) ),
			//TODO: test more types
			//TODO: test options
		);
	}

	/**
	 * @dataProvider provideParseValues
	 */
	public function testParseValues( $params, $expected ) {
		$params['action'] = 'wbparsevalue';
		list( $result,, ) = $this->doApiRequest( $params );

		// -- check the result ------------------------------------------------
		$this->assertArrayHasKey( 'results', $result, "Missing 'results' section in response." );

		/* @var DataValue $expectedValue */
		$i = 0;
		foreach ( $expected as $expectedValue ) {
			$this->assertArrayHasKey( $i, $result['results'], "Missing result no. $i" );
			$actualResult = $result['results'][$i];

			if ( is_object( $expectedValue ) ) {
				$expectedValue = $expectedValue->toArray();

				// ignore "raw", etc.
				$actualResult = array_intersect_assoc( $actualResult, $expectedValue );
			}

			$this->assertArrayEquals( $expectedValue, $actualResult, false, true );
			$i++;
		}
	}

	public static function provideParseValuesErrors() {
		return array(
			'parse string value' => array(
				'p' => array( 'parser' => 'int', 'values' => 'xyz', 'options' => '' ),
				'e' => array( 'wikibase-parse-error' ) )
		);
	}

	/**
	 * @dataProvider provideParseValuesErrors
	 */
	public function testParseValuesErrors( $params, $expected ) {
		$params['action'] = 'wbparsevalue';
		list( $result,, ) = $this->doApiRequest( $params );

		// -- check the result ------------------------------------------------
		$this->assertArrayHasKey( 'results', $result, "Missing 'results' section in response." );

		/* @var DataValue $expectedValue */
		$i = 0;
		foreach ( $expected as $expectedError ) {
			$this->assertArrayHasKey( $i, $result['results'], "Missing result no. $i" );
			$actualResult = $result['results'][$i];

			$this->assertArrayHasKey( 'error', $actualResult, "Missing 'error' field in error for result no. $i." );
			$this->assertArrayHasKey( 'error-info', $actualResult, "Missing 'error-info' field in error for result no. $i." );
			$this->assertArrayHasKey( 'error-message', $actualResult, "Missing 'error-message' field in error for result no. $i." );
			$this->assertArrayHasKey( 'name', $actualResult['error-message'], "Missing 'name' field in error message for result no. $i." );
			$this->assertArrayHasKey( 'error-html', $actualResult, "Missing 'error-html' field in error for result no. $i." );

			$this->assertEquals( $expectedError, $actualResult['error-message']['name'], "error message for result no $1" );
			$this->assertEquals( 'error', $actualResult['error-message']['type'], "type of error on result no $1" );
			$this->assertValidHtmlSnippet( $actualResult['error-html'] );
			$i++;
		}
	}

	public static function provideParseValuesUsageExceptions() {
		return array(
			'bad parser' => array(
				'p' => array( 'parser' => 'blabla', 'values' => 'Hello', 'options' => '' ),
				'e' => 'unknown_parser' ),
			'bad options' => array(
				'p' => array( 'parser' => 'null', 'values' => 'Hello', 'options' => '{{' ),
				'e' => 'malformed-options' ),
		);
	}

	/**
	 * @dataProvider provideParseValuesUsageExceptions
	 */
	public function testParseValuesUsageException( $params, $expectedCode ) {
		try {
			$params['action'] = 'wbparsevalue';
			$this->doApiRequest( $params );

			$this->fail( 'Did not throw a UsageException as expected!' );
		} catch ( UsageException $ex ) {
			$this->assertEquals( $expectedCode, $ex->getCodeString() );
		}
	}

}
