<?php

namespace Wikibase\Lib\Serializers;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\EntityIdParser;
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
 */
class ByPropertyListUnserializer implements Unserializer {

	/**
	 * @since 0.2
	 *
	 * @var Serializer
	 */
	protected $elementUnserializer;

	/**
	 * Constructor.
	 *
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
		$idParser = new EntityIdParser();

		foreach ( $serialization as $propertyId => $byPropId ) {
			if ( !is_array( $byPropId ) ) {
				throw new InvalidArgumentException( "Element with key '$propertyId' should be an array, found " . gettype( $byPropId ) );
			}

			$propertyId = $idParser->parse( $propertyId );

			foreach ( $byPropId as $serializedElement ) {
				$element = $this->elementUnserializer->newFromSerialization( $serializedElement );

				/** @var PropertyId $elementPropertyId */
				$elementPropertyId = $element->getPropertyId();

				if ( !$elementPropertyId->equals( $propertyId ) ) {
					throw new OutOfBoundsException( "Element with id '" . $elementPropertyId->getSerialization() .
					"' found in list with id '" . $propertyId->getSerialization() . "'" );
				}

				$elements[] = $element;
			}
		}

		return $elements;
	}

}
