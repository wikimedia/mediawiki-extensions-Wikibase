<?php

namespace Wikibase\Test;

use Wikibase\Lib\Serializers\ClaimSerializer;
use Wikibase\Lib\Serializers\PropertySerializer;
use Wikibase\Lib\Serializers\SnakSerializer;
use Wikibase\Property;

/**
 * @covers Wikibase\Lib\Serializers\PropertySerializer
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
	 * @return string
	 */
	protected function getClass() {
		return '\Wikibase\Lib\Serializers\PropertySerializer';
	}

	/**
	 * @return PropertySerializer
	 */
	protected function getInstance() {
		$class = $this->getClass();
		return new $class( new ClaimSerializer( new SnakSerializer() ) );
	}

	/**
	 * @see EntitySerializerBaseTest::getEntityInstance
	 *
	 * @return Property
	 */
	protected function getEntityInstance() {
		$property = Property::newFromType( 'string' );
		$property->setId( 42 );
		return $property;
	}

	/**
	 * @see SerializerBaseTest::validProvider
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
