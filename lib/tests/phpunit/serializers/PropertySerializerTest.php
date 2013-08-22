<?php

namespace Wikibase\Test;

use DataTypes\DataType;
use Wikibase\Property;

/**
 * @covers Wikibase\Lib\Serializers\PropertySerializer
 *
 * @file
 * @since 0.3
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
class PropertySerializerTest extends EntitySerializerBaseTest {

	/**
	 * @see SerializerBaseTest::getClass
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	protected function getClass() {
		return '\Wikibase\Lib\Serializers\PropertySerializer';
	}

	/**
	 * @see EntitySerializerBaseTest::getEntityInstance
	 *
	 * @since 0.3
	 *
	 * @return Property
	 */
	protected function getEntityInstance() {
		$property = Property::newEmpty();
		$property->setId( 42 );
		return $property;
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

		$validArgs = $this->arrayWrap( $validArgs );

		$property = $this->getEntityInstance();

		$property->setDataTypeId( 'string' );

		$validArgs[] = array(
			$property,
			array(
				'id' => $this->getFormattedIdForEntity( $property ),
				'type' => $property->getType(),
				'datatype' => $property->getDataTypeId()
			)
		);

		return $validArgs;
	}

}
