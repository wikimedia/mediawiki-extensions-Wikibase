<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;
use OutOfBoundsException;

/**
 * Factory for constructing Serializer and Unserializer objects.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SerializerFactory {

	/**
	 * @param mixed $object
	 * @param SerializationOptions $options
	 *
	 * @return Serializer
	 * @throws OutOfBoundsException
	 * @throws InvalidArgumentException
	 */
	public function newSerializerForObject( $object, $options = null ) {
		if ( !is_object( $object ) ) {
			throw new InvalidArgumentException( 'newSerializerForObject only accepts objects and got ' . gettype( $object ) );
		}

		//TODO: The factory should take options in the constructor.
		//TODO: The factory should offer clones of the options via newSerializationOptions().
		//TODO: This method should merge to options given with the options from the constructor.

		if ( $options == null ) {
			$options = new SerializationOptions();
		}

		switch ( true ) {
			case ( $object instanceof \Wikibase\Snak ):
				return new SnakSerializer( $options );
			case ( $object instanceof \Wikibase\Reference ):
				return new ReferenceSerializer( $options );
			case ( $object instanceof \Wikibase\Item ):
				return new ItemSerializer( $options );
			case ( $object instanceof \Wikibase\Property ):
				return new PropertySerializer( $options );
			case ( $object instanceof \Wikibase\Entity ):
				return new EntitySerializer( $options );
			case ( $object instanceof \Wikibase\Claim ):
				return new ClaimSerializer( $options );
			case ( $object instanceof \Wikibase\Claims ):
				return new ClaimsSerializer( $options );
		}

		throw new OutOfBoundsException( 'There is no serializer for the provided type of object "' . get_class( $object ) . '"' );
	}

	/**
	 * @param string $className
	 * @param SerializationOptions $options
	 *
	 * @return Unserializer
	 * @throws OutOfBoundsException
	 * @throws InvalidArgumentException
	 */
	public function newUnserializerForClass( $className, $options = null ) {
		if ( $options === null ) {
			$options = new SerializationOptions();
		}

		//TODO: The factory should take options in the constructor.
		//TODO: The factory should offer clones of the options via newSerializationOptions().
		//TODO: This method should merge to options given with the options from the constructor.

		if ( !is_string( $className ) ) {
			throw new OutOfBoundsException( '$className needs to be a string' );
		}

		switch ( ltrim( $className, '\\' ) ) {
			case 'Wikibase\Item':
				return new ItemSerializer( $options );
			case 'Wikibase\Property':
				return new PropertySerializer( $options );
			case 'Wikibase\Snak':
				return new SnakSerializer( $options );
			case 'Wikibase\Reference':
				return new ReferenceSerializer( $options );
			case 'Wikibase\Claim':
				return new ClaimSerializer( $options );
			case 'Wikibase\Claims':
				return new ClaimsSerializer( $options );
		}

		throw new OutOfBoundsException( '"' . $className . '" has no associated unserializer' );
	}

	/**
	 * @param string $entityType
	 * @param $options
	 *
	 * @throws InvalidArgumentException
	 * @return Unserializer
	 */
	public function newUnserializerForEntity( $entityType, $options ) {
		switch( $entityType ) {
			case 'wikibase-item':
				return new ItemSerializer( $options );
			case 'wikibase-property':
				return new PropertySerializer( $options );
			default:
				throw new InvalidArgumentException( '$entityType is invalid' );
		}
	}

}
