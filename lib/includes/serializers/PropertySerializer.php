<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;
use Wikibase\Entity;
use Wikibase\Property;

/**
 * Serializer for properties.
 *
 * @since 0.3
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PropertySerializer extends EntitySerializer implements Unserializer {

	/**
	 * @see EntitySerializer::getEntityTypeSpecificSerialization
	 *
	 * @since 0.3
	 *
	 * @param Entity $property
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	protected function getEntityTypeSpecificSerialization( Entity $property ) {
		if ( !( $property instanceof Property ) ) {
			throw new InvalidArgumentException( 'PropertySerializer can only serialize '
				. 'Property implementing objects' );
		}

		$serialization = array();

		if ( in_array( 'datatype', $this->options->getProps() ) ) {
			$serialization['datatype'] = $property->getDataTypeId();
		}

		return $serialization;
	}

	/**
	 * @param array $data
	 *
	 * $return Property
	 * @throws InvalidArgumentException
	 */
	public function newFromSerialization( array $data ) {
		$entity = parent::newFromSerialization( $data );

		if ( !array_key_exists( 'datatype', $data ) ) {
			throw new InvalidArgumentException( 'Property data type missing in serialization.' );
		}

		$entity->setDataTypeId( $data['datatype'] );

		return $entity;
	}

}
