<?php

namespace Wikibase\Lib\Test\Serializers;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Serializers\ByPropertyListUnserializer;
use Wikibase\Lib\Serializers\SnakSerializer;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\SnakList;

/**
 * @covers Wikibase\Lib\Serializers\ByPropertyListUnserializer
 *
 * @file
 * @since 0.4
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
class ByPropertyListUnserializerTest extends UnserializerBaseTest {

	/**
	 * @see UnserializerBaseTest::getClass
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function getClass() {
		return '\Wikibase\Lib\Serializers\ByPropertyListUnserializer';
	}

	/**
	 * @see UnserializerBaseTest::getInstance
	 *
	 * @since 0.4
	 *
	 * @return ByPropertyListUnserializer
	 */
	protected function getInstance() {
		$snakSetailizer = new SnakSerializer();
		return new ByPropertyListUnserializer( $snakSetailizer );
	}

	/**
	 * @see UnserializerBaseTest::validProvider
	 *
	 * @since 0.4
	 *
	 * @return array
	 */
	public function validProvider() {
		$validArgs = array();

		$dataValue0 = new \DataValues\StringValue( 'ohi' );

		$id42 = new PropertyId( 'P42' );
		$id2 = new PropertyId( 'P2' );

		$snak0 = new PropertyNoValueSnak( $id42 );
		$snak1 = new PropertySomeValueSnak( $id2 );
		$snak2 = new PropertyValueSnak( $id2, $dataValue0 );

		$validArgs[] = new SnakList( array( $snak0, $snak1, $snak2 ) );

		$validArgs = $this->arrayWrap( $validArgs );

		$validArgs[] = array(
			array(),
			new SnakList(),
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
			new SnakList( array( $snak0, $snak1, $snak2 ) ),
		);

		return $validArgs;
	}

}
