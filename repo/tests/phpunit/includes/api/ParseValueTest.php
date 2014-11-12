<?php

namespace Wikibase\Test\Api;

/**
 * @covers Wikibase\Api\ParseValue
 *
 * @group Database
 * @group medium
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ParseValueTest extends WikibaseApiTestCase {

	protected function setUp() {
		$this->mergeMwGlobalArrayValue(
			'wgValueParsers',
			array( 'decimal' => 'ValueParsers\DecimalParser' )
		);
		parent::setUp();
	}

	public function provideValid() {
		return array(
			'null' => array(
				'$text' => 'foo',
				'$parser' => 'null',
				'$expected' => array(
					'0/raw' => 'foo',
					'0/type' => 'unknown',
					'0/value' => 'foo',
				),
			),

			'decimal' => array(
				'$text' => '123.456',
				'$parser' => 'decimal',
				'$expected' => array(
					'0/raw' => '123.456',
					'0/type' => 'decimal',
					'0/value' => '123.456',
				),
			),

			'multi decimals' => array(
				'$text' => '123|-17',
				'$parser' => 'decimal',
				'$expected' => array(
					'0/raw' => '123',
					'0/type' => 'decimal',
					'0/value' => '123',

					'1/raw' => '-17',
					'1/type' => 'decimal',
					'1/value' => '-17',
				),
			),

			'quantity' => array(
				'$text' => '123.456+/-0.003',
				'$parser' => 'quantity',
				'$expected' => array(
					'0/raw' => '123.456+/-0.003',
					'0/type' => 'quantity',
					'0/value/amount' => '+123.456',
					'0/value/unit' => '1',
					'0/value/upperBound' => '+123.459',
					'0/value/lowerBound' => '+123.453',
				),
			),

			'empty decimal' => array(
				'$text' => '|',
				'$parser' => 'decimal',
				'$expected' => array(
					'0/raw' => '',
					'0/error' => 'ValueParsers\ParseException',
					'0/error-info' => '/^.+$/',
					'0/expected-format' => 'decimal',
					'0/messages/0/name' => 'wikibase-parse-error',
					'0/messages/0/html/*' => '/^.+$/',
				),
			),

			'malformed decimal' => array(
				'$text' => 'foo',
				'$parser' => 'decimal',
				'$expected' => array(
					'0/raw' => 'foo',
					'0/error' => 'ValueParsers\ParseException',
					'0/error-info' => '/^.+$/',
					'0/expected-format' => 'decimal',
					'0/messages/0/name' => 'wikibase-parse-error',
					'0/messages/0/html/*' => '/^.+$/',
				),
			),

			'good and bad' => array(
				'$text' => 'foo|2',
				'$parser' => 'decimal',
				'$expected' => array(
					'0/error' => 'ValueParsers\ParseException',
					'1/value' => '2',
				),
			),

		);
	}

	protected function assertValueAtPath( $expected, $path, $data ) {
		$name = '';
		foreach ( $path as $step ) {
			$this->assertArrayHasKey( $step, $data );
			$data = $data[$step];
			$name .= '/' . $step;
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
	public function testParse( $text, $parser, $expected ) {
		$params = array(
			'action' => 'wbparsevalue',
			'values' => $text,
			'parser' => $parser
		);

		list( $result, , ) = $this->doApiRequest( $params );

		$this->assertArrayHasKey( 'results', $result );

		foreach ( $expected as $path => $value ) {
			$path = explode( '/', $path );
			$this->assertValueAtPath( $value, $path, $result['results'] );
		}
	}

}
