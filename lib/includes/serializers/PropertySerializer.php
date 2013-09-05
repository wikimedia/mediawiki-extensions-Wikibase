<?php

namespace Wikibase\Lib\Serializers;

use MWException;
use Wikibase\Entity;
use Wikibase\Property;

/**
 * Serializer for properties.
 *
 * See docs/json.wiki for details of the format.
 *
 * @since 0.3
 *
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertySerializer extends EntitySerializer {

	/**
	 * @see EntitySerializer::getEntityTypeSpecificSerialization
	 *
	 * @since 0.3
	 *
	 * @param Entity $property
	 *
	 * @return array
	 * @throws MWException
	 */
	protected function getEntityTypeSpecificSerialization( Entity $property ) {
		if ( !( $property instanceof Property ) ) {
			throw new MWException( 'PropertySerializer can only serialize Property implementing objects' );
		}

		//NOTE: when changing the serialization structure, update docs/json.wiki too!

		$serialization = array();

		if ( in_array( 'datatype', $this->options->getProps() ) ) {
			$serialization['datatype'] = $property->getDataTypeId();
		}

		return $serialization;
	}

}
