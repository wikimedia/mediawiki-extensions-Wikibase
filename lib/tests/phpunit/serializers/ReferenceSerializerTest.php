<?php

namespace Wikibase\Test;

use Wikibase\Lib\Serializers\SnakSerializer;

/**
 * @covers Wikibase\Lib\Serializers\ReferenceSerializer
 *
 * @file
 * @since 0.3
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ReferenceSerializerTest extends SerializerBaseTest {

	/**
	 * @see SerializerBaseTest::getClass
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	protected function getClass() {
		return '\Wikibase\Lib\Serializers\ReferenceSerializer';
	}

	/**
	 * @see SerializerBaseTest::validProvider
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function validProvider() {
		$validArgs = array();

		$snaks =  array(
			new \Wikibase\PropertyNoValueSnak( 42 ),
			new \Wikibase\PropertySomeValueSnak( 1 ),
			new \Wikibase\PropertySomeValueSnak( 42 ),
			new \Wikibase\PropertyValueSnak( 1, new \DataValues\StringValue( 'foobar' ) ),
			new \Wikibase\PropertyValueSnak( 9001, new \DataValues\StringValue( 'foobar' ) ),
		);

		$snakSerializer = new SnakSerializer();

		$reference = new \Wikibase\Reference( new \Wikibase\SnakList( $snaks ) );

		$validArgs[] = array(
			$reference,
			array(
				'hash' => $reference->getHash(),
				'snaks' => array(
					'P42' => array(
						$snakSerializer->getSerialized( $snaks[0] ),
						$snakSerializer->getSerialized( $snaks[2] ),
					),
					'P1' => array(
						$snakSerializer->getSerialized( $snaks[1] ),
						$snakSerializer->getSerialized( $snaks[3] ),
					),
					'P9001' => array(
						$snakSerializer->getSerialized( $snaks[4] ),
					),
				),
			),
		);

		return $validArgs;
	}

}
