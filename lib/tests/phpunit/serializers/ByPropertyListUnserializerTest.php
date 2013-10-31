<?php

namespace Wikibase\Lib\Test\Serializers;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Serializers\ByPropertyListUnserializer;
use Wikibase\Lib\Serializers\SnakSerializer;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;

/**
 * @covers Wikibase\Lib\Serializers\ByPropertyListUnserializer
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Adam Shorland
 */
class ByPropertyListUnserializerTest extends \MediaWikiTestCase {

	/**
	 * @return string
	 */
	protected function getClass() {
		return '\Wikibase\Lib\Serializers\ByPropertyListUnserializer';
	}

	/**
	 * @return ByPropertyListUnserializer
	 */
	protected function getInstance() {
		$snakSerializer = new SnakSerializer();
		return new ByPropertyListUnserializer( $snakSerializer );
	}

	/**
	 * @dataProvider validProvider
	 */
	public function testGetUnserializedValid( array $input, $expected ) {
		$unserializer = $this->getInstance();
		$unserialized = $unserializer->newFromSerialization( $input );
		$this->assertEquals( $expected, $unserialized );
	}

	public function validProvider() {
		$validArgs = array();

		$dataValue0 = new StringValue( 'ohi' );

		$id42 = new PropertyId( 'P42' );
		$id2 = new PropertyId( 'P2' );

		$snak0 = new PropertyNoValueSnak( $id42 );
		$snak1 = new PropertySomeValueSnak( $id2 );
		$snak2 = new PropertyValueSnak( $id2, $dataValue0 );

		$validArgs[] = array(
			array(),
			array(),
		);

		$validArgs[] = array(
			array(
				'P42' => array(
				),
				'P2' => array(
				),
			),
			array(),
		);

		$validArgs[] = array(
			array(
				'P42' => array(
					0 => array(
						'snaktype' => 'novalue',
						'property' => 'P42',
					),
				),
				'P2' => array(
					0 => array(
						'snaktype' => 'somevalue',
						'property' => 'P2',
					),
					1 => array(
						'snaktype' => 'value',
						'property' => 'P2',
						'datavalue' => $dataValue0->toArray(),
					),
				),
			),
			array( $snak0, $snak1, $snak2 ),
		);

		return $validArgs;
	}

	public function invalidProvider() {
		$invalid = array(
			false,
			true,
			null,
			42,
			4.2,
			'',
			'foo bar baz',
		);

		return $this->arrayWrap( $this->arrayWrap( $invalid ) );
	}

	/**
	 * @dataProvider invalidProvider
	 */
	public function testNewFromSerializationInvalid( $input ) {
		$serializer = $this->getInstance();
		$this->assertException( function() use ( $serializer, $input ) { $serializer->newFromSerialization( $input ); } );
	}

}
