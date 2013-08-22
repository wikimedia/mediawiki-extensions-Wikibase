<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\PropertyId;

/**
 * @covers Wikibase\Lib\Serializers\SnakSerializer
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
class SnakSerializerTest extends SerializerBaseTest {

	/**
	 * @see SerializerBaseTest::getClass
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	protected function getClass() {
		return '\Wikibase\Lib\Serializers\SnakSerializer';
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

		$id = new PropertyId( 'P42' );

		$validArgs[] = array(
			new \Wikibase\PropertyNoValueSnak( $id ),
			array(
				'snaktype' => 'novalue',
				'property' => 'P42',
			)
		);

		$validArgs[] = array(
			new \Wikibase\PropertySomeValueSnak( $id ),
			array(
				'snaktype' => 'somevalue',
				'property' => 'P42',
			)
		);

		$dataValue = new \DataValues\StringValue( 'ohi' );

		$validArgs[] = array(
			new \Wikibase\PropertyValueSnak( $id, $dataValue ),
			array(
				'snaktype' => 'value',
				'property' => 'P42',
				'datavalue' => $dataValue->toArray(),
			)
		);

		return $validArgs;
	}

}
