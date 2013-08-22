<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Serializers\ByPropertyListSerializer;
use Wikibase\Lib\Serializers\SnakSerializer;

/**
 * @covers Wikibase\Lib\Serializers\ByPropertyListSerializer
 *
 * @file
 * @since 0.2
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ByPropertyListSerializerTest extends SerializerBaseTest {

	/**
	 * @see SerializerBaseTest::getClass
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	protected function getClass() {
		return '\Wikibase\Lib\Serializers\ByPropertyListSerializer';
	}

	/**
	 * @since 0.2
	 *
	 * @return ByPropertyListSerializer
	 */
	protected function getInstance() {
		$snakSetailizer = new SnakSerializer();
		return new ByPropertyListSerializer( 'test', $snakSetailizer );
	}

	/**
	 * @see SerializerBaseTest::validProvider
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function validProvider() {
		$validArgs = array();

		$dataValue0 = new \DataValues\StringValue( 'ohi' );

		$id42 = new PropertyId( 'p42' );
		$id2 = new PropertyId( 'p2' );

		$snak0 = new \Wikibase\PropertyNoValueSnak( $id42 );
		$snak1 = new \Wikibase\PropertySomeValueSnak( $id2 );
		$snak2 = new \Wikibase\PropertyValueSnak( $id2, $dataValue0 );

		$validArgs[] = new \Wikibase\SnakList( array( $snak0, $snak1, $snak2 ) );

		$validArgs = $this->arrayWrap( $validArgs );

		$validArgs[] = array(
			new \Wikibase\SnakList(),
			array(),
		);

		$validArgs[] = array(
			new \Wikibase\SnakList( array( $snak0, $snak1, $snak2 ) ),
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
		);

		return $validArgs;
	}

}
