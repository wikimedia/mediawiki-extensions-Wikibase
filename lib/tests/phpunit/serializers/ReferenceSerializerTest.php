<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\Lib\Serializers\ReferenceSerializer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SnakSerializer;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\Reference;
use Wikibase\SnakList;

/**
 * @covers Wikibase\Lib\Serializers\ReferenceSerializer
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
	 * @return string
	 */
	protected function getClass() {
		return '\Wikibase\Lib\Serializers\ReferenceSerializer';
	}

	/**
	 * @return ReferenceSerializer
	 */
	protected function getInstance() {
		$class = $this->getClass();
		return new $class( new SnakSerializer() );
	}

	/**
	 * @see SerializerBaseTest::validProvider
	 *
	 * @return array
	 */
	public function validProvider() {
		$validArgs = array();

		$snaks =  array(
			new PropertyNoValueSnak( 42 ),
			new PropertySomeValueSnak( 1 ),
			new PropertySomeValueSnak( 42 ),
			new PropertyValueSnak( 1, new StringValue( 'foobar' ) ),
			new PropertyValueSnak( 9001, new StringValue( 'foobar' ) ),
		);

		$snakSerializer = new SnakSerializer();

		$reference = new Reference( new SnakList( $snaks ) );
		$sortedReference = new Reference( new SnakList(
			array( $snaks[0], $snaks[2], $snaks[1], $snaks[3], $snaks[4] )
		) );

		$validArgs['sorted'] = array(
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
				'snaks-order' => array(
					'P42',
					'P1',
					'P9001',
				)
			),
			null,
			$sortedReference,
		);

		$opts = new SerializationOptions();
		$opts->setOption( SerializationOptions::OPT_GROUP_BY_PROPERTIES, array() );
		$snakSerializer = new SnakSerializer( null, $opts );

		$validArgs['list'] = array(
			$reference,
			array(
				'hash' => $reference->getHash(),
				'snaks' => array(
					$snakSerializer->getSerialized( $snaks[0] ),
					$snakSerializer->getSerialized( $snaks[1] ),
					$snakSerializer->getSerialized( $snaks[2] ),
					$snakSerializer->getSerialized( $snaks[3] ),
					$snakSerializer->getSerialized( $snaks[4] ),
				),
				'snaks-order' => array(
					'P42',
					'P1',
					'P9001',
				)
			),
			$opts,
			$sortedReference,
		);

		return $validArgs;
	}

}
