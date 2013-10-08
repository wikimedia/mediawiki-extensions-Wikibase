<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;
use OutOfBoundsException;
use Wikibase\Claim;
use Wikibase\Claims;
use Wikibase\Entity;
use Wikibase\Item;
use Wikibase\Property;
use Wikibase\Reference;
use Wikibase\Snak;

/**
 * Factory for constructing Serializer and Unserializer objects.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class SerializerFactory {

	/**
	 * @var SerializationOptions
	 */
	protected $options;

	/**
	 * @param SerializationOptions $options Default options.
	 */
	public function __construct( SerializationOptions $options ) {
		$this->options = $options;
	}

	/**
	 * Returns a new SerializationOptions object, based on both, the options provided to the
	 * factory's constructor, and the $extraOptions.
	 *
	 * @param array $extraOptions An associative array of options.
	 *
	 * @return SerializationOptions
	 */
	public function newSerializationOptions( array $extraOptions = array() ) {
		$options = clone $this->options;
		$options->setOptions( $extraOptions );
		return $options;
	}

	/**
	 * @param mixed $object
	 * @param SerializationOptions $options
	 *
	 * @return Serializer
	 * @throws OutOfBoundsException
	 * @throws InvalidArgumentException
	 */
	public function newSerializerForObject( $object, SerializationOptions $options = null ) {
		if ( !is_object( $object ) ) {
			throw new InvalidArgumentException( 'newSerializerForObject only accepts objects and got ' . gettype( $object ) );
		}

		$options = $this->newSerializationOptions( $options == null ? array() : $options->getOptions() );

		switch ( true ) {
			case ( $object instanceof Snak ):
				return new SnakSerializer( $options );
			case ( $object instanceof Reference ):
				return new ReferenceSerializer( $options );
			case ( $object instanceof Item ):
				return new ItemSerializer( $options );
			case ( $object instanceof Property ):
				return new PropertySerializer( $options );
			case ( $object instanceof Entity ):
				return new EntitySerializer( $options );
			case ( $object instanceof Claim ):
				return new ClaimSerializer( $options );
			case ( $object instanceof Claims ):
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
		if ( !is_string( $className ) ) {
			throw new OutOfBoundsException( '$className needs to be a string' );
		}

		$options = $this->newSerializationOptions( $options == null ? array() : $options->getOptions() );

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
	 *
	 * @throws OutOfBoundsException
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
