<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Serializers\ListSerializer;
use Wikibase\Lib\Serializers\SnakSerializer;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\SnakList;

/**
 * @covers Wikibase\Lib\Serializers\ListSerializer
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ListSerializerTest extends SerializerBaseTest {

	/**
	 * @see SerializerBaseTest::getClass
	 *
	 * @return string
	 */
	protected function getClass() {
		return '\Wikibase\Lib\Serializers\ListSerializer';
	}

	/**
	 * @return ListSerializer
	 */
	protected function getInstance() {
		$snakSerializer = new SnakSerializer();
		return new ListSerializer( 'foo' ,$snakSerializer );
	}

	/**
	 * @see SerializerBaseTest::validProvider
	 *
	 * @return array
	 */
	public function validProvider() {
		$validArgs = array();

		$dataValue0 = new StringValue( 'ohi' );

		$id42 = new PropertyId( 'p42' );
		$id2 = new PropertyId( 'p2' );

		$snak0 = new PropertyNoValueSnak( $id42 );
		$snak1 = new PropertySomeValueSnak( $id2 );
		$snak2 = new PropertyValueSnak( $id2, $dataValue0 );

		$validArgs[] = new SnakList( array( $snak0, $snak1, $snak2 ) );

		$validArgs = $this->arrayWrap( $validArgs );

		$validArgs[ 'Empty' ] = array(
			new SnakList(),
			array(),
		);

		$validArgs[ 'AList' ] = array(
			new SnakList( array( $snak0, $snak1, $snak2 ) ),
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
		);

		return $validArgs;
	}

}