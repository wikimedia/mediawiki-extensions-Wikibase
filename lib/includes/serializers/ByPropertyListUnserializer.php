<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;
use OutOfBoundsException;

/**
 * Serializer for Traversable objects that need to be grouped
 * per property id. Each element needs to have a getPropertyId method.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class ByPropertyListUnserializer implements Unserializer {

	/**
	 * @since 0.2
	 *
	 * @var Serializer
	 */
	private $elementUnserializer;

	/**
	 * @since 0.2
	 *
	 * @param Unserializer $elementUnserializer
	 */
	public function __construct( Unserializer $elementUnserializer ) {
		$this->elementUnserializer = $elementUnserializer;
	}

	/**
	 * @see Unserializer::newFromSerialization
	 *
	 * @since 0.4
	 *
	 * @param array $serialization
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 * @throws OutOfBoundsException
	 */
	public function newFromSerialization( array $serialization ) {
		$elements = array();

		foreach ( $serialization as $propertyId => $byPropId ) {
			if ( !is_array( $byPropId ) ) {
				throw new InvalidArgumentException( "Element with key '$propertyId' should be an array, found " . gettype( $byPropId ) );
			}

			foreach ( $byPropId as $serializedElement ) {
				$element = $this->elementUnserializer->newFromSerialization( $serializedElement );

				$elementPropertyId = $element->getPropertyId()->getSerialization();

				if ( strtoupper( $elementPropertyId ) !== strtoupper( $propertyId ) ) {
					throw new OutOfBoundsException( "Element with id '" . $elementPropertyId .
					"' found in list with id '" . $propertyId . "'" );
				}

				$elements[] = $element;
			}
		}

		return $elements;
	}

}
