<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Serializers\ListUnserializer;
use Wikibase\Lib\Serializers\SnakSerializer;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;

/**
 * @covers Wikibase\Lib\Serializers\ListUnserializer
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ListUnserializerTest extends \MediaWikiTestCase {

	/**
	 * @return string
	 */
	protected function getClass() {
		return '\Wikibase\Lib\Serializers\ListUnserializer';
	}

	/**
	 * @return ListUnserializer
	 */
	protected function getInstance() {
		$snakSerializer = new SnakSerializer();
		return new ListUnserializer( $snakSerializer );
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

		//0 empty serialization 1
		$validArgs[] = array(
			array(),
			array(),
		);

		//1 serialization
		$validArgs[] = array(
			array(
				0 => array(
					'snaktype' => 'novalue',
					'property' => 'P42',
				),
				1 => array(
					'snaktype' => 'somevalue',
					'property' => 'P2',
				),
				2 => array(
					'snaktype' => 'value',
					'property' => 'P2',
					'datavalue' => $dataValue0->toArray(),
				),
			),
			array( $snak0, $snak1, $snak2 ),
		);

		return $validArgs;
	}

	public function invalidProvider() {
		$invalid = array(
			array( false ),
			array( true ),
			array( null ),
			array( 42 ),
			array( 4.2 ),
			array( '' ),
			array( 'foo bar baz' ),
		);

		return $this->arrayWrap( $this->arrayWrap( $invalid ) );
	}

	/**
	 * @dataProvider invalidProvider
	 */
	public function testNewFromSerializationInvalid( $input ) {
		$this->setExpectedException( 'Exception' );
		$serializer = $this->getInstance();
		$serializer->newFromSerialization( $input );
	}

}