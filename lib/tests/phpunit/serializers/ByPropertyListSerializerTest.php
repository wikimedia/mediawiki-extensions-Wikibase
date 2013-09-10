<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Serializers\ByPropertyListSerializer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SnakSerializer;
use Wikibase\PropertyNoValueSnak;

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
		$snakSerializer = new SnakSerializer();
		return new ByPropertyListSerializer( 'test', $snakSerializer );
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

	/**
	 * @dataProvider provideIdKeyMode
	 */
	public function testIdKeyMode( $mode ) {
		$snakSerializer = new SnakSerializer();
		$snak = new PropertyNoValueSnak( new PropertyId( "P123" ) );

		$options = new SerializationOptions();
		$options->setIdKeyMode( $mode );
		$serializer = new ByPropertyListSerializer( 'test', $snakSerializer, $options );

		$data = $serializer->getSerialized( new \ArrayObject( array( $snak ) ) );
		$this->assertEquals( ( $mode & SerializationOptions::ID_KEYS_UPPER ) > 0, array_key_exists( 'P123', $data ), 'upper case key' );
		$this->assertEquals( ( $mode & SerializationOptions::ID_KEYS_LOWER ) > 0, array_key_exists( 'p123', $data ), 'lower case key' );
	}

	public function provideIdKeyMode() {
		return array(
			'lower' => array( SerializationOptions::ID_KEYS_LOWER ),
			'upper' => array( SerializationOptions::ID_KEYS_UPPER ),
			'both' => array( SerializationOptions::ID_KEYS_BOTH ),
		);
	}
}
