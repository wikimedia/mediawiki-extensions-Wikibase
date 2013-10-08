<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Serializers\SnakSerializer;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;

/**
 * @covers Wikibase\Lib\Serializers\SnakSerializer
 *
 * @since 0.2
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
	 * @since 0.2
	 *
	 * @return SnakSerializer
	 */
	protected function getInstance() {
		$dataTypeLookup = $this->getMock( 'Wikibase\Lib\PropertyDataTypeLookup' );
		$dataTypeLookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnValue( 'test' ) );

		$options = $this->getSerializationOptions();

		$class = $this->getClass();
		return new $class( $options, $dataTypeLookup );
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
			new PropertyNoValueSnak( $id ),
			array(
				'snaktype' => 'novalue',
				'property' => 'P42',
			)
		);

		$validArgs[] = array(
			new PropertySomeValueSnak( $id ),
			array(
				'snaktype' => 'somevalue',
				'property' => 'P42',
			)
		);

		$dataValue = new StringValue( 'ohi' );

		$validArgs[] = array(
			new PropertyValueSnak( $id, $dataValue ),
			array(
				'snaktype' => 'value',
				'property' => 'P42',
				'datatype' => 'test', // from the PropertyDataTypeLookupMock defined in getInstance()
				'datavalue' => $dataValue->toArray(),
			)
		);

		return $validArgs;
	}

}
