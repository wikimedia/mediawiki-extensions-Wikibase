<?php

namespace ValueParsers\Test;

/**
 * @covers ValueParsers\ApiParseValue
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ApiParseValueTest extends \ApiTestCase {

	public function testApiRequest() {
		$params = array(
			'action' => 'parsevalue',
			'parser' => 'globecoordinate',
			'values' => '4,2|0,0',
		);

		list( $resultArray, ) = $this->doApiRequest( $params );

		$this->assertIsResultArray( $resultArray );
	}

	protected function assertIsResultArray( $resultArray ) {
		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'results', $resultArray, 'top level element has a results key' );

		foreach ( $resultArray['results'] as $result ) {
			$this->assertIsGeoValueArray( $result );
		}
	}

	protected function assertIsGeoValueArray( $result ) {
		$this->assertInternalType( 'array', $result, 'result is an array' );

		$this->assertArrayHasKey( 'value', $result, 'result has a value key' );
		$this->assertArrayHasKey( 'raw', $result, 'result has a raw key' );
		$this->assertArrayHasKey( 'type', $result, 'result has a type key' );

		$value = $result['value'];

		$this->assertInternalType( 'array', $value, 'value key points to an array' );

		$this->assertArrayHasKey( 'latitude', $value, 'value has latitude key' );
		$this->assertArrayHasKey( 'longitude', $value, 'value has longitude key' );
		$this->assertArrayHasKey( 'altitude', $value, 'value has altitude key' );
		$this->assertArrayHasKey( 'precision', $value, 'value has precision key' );
		$this->assertArrayHasKey( 'globe', $value, 'value has globe key' );
	}

}
