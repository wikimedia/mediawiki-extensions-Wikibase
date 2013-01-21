<?php

namespace Wikibase;
use MWException;

class SerializerFactory {

	public function __construct(  ) {

	}

	/**
	 * @param mixed $object
	 *
	 * @return Serializer
	 * @throws MWException
	 */
	public function newSerializerForObject( $object ) {
		if ( !is_object( $object ) ) {
			throw new MWException( 'newSerializerForObject only accepts objects and got ' . gettype( $object ) );
		}

		switch ( true ) {
			case ( $object instanceof \Wikibase\Snak ):
				return new SnakSerializer();
				break;
			case ( $object instanceof \Wikibase\Reference ):
				return new ReferenceSerializer();
				break;
			case ( $object instanceof \Wikibase\Item ):
				return new ItemSerializer();
				break;
			case ( $object instanceof \Wikibase\Property ):
				return new PropertySerializer();
				break;
			case ( $object instanceof \Wikibase\Entity ):
				return new EntitySerializer();
				break;
			case ( $object instanceof \Wikibase\Claim ):
				return new ClaimSerializer();
				break;
			case ( $object instanceof \Wikibase\Claims ):
				return new ClaimsSerializer();
				break;
		}

		throw new MWException( 'There is no serializer for the provided type of object "' . get_class( $object ) . '"' );
	}

	/**
	 * @param string $className
	 *
	 * @return Unserializer
	 * @throws MWException
	 */
	public function newUnserializerForClass( $className ) {
		if ( !is_string( $className ) ) {
			throw new MWException( '$className needs to be a string' );
		}

		switch ( $className ) {
			case 'Wikibase\Snak':
				return new SnakSerializer();
				break;
			case 'Wikibase\Claim':
				return new ClaimSerializer();
				break;
		}

		throw new MWException( '"' . $className . '" has no associated unserializer' );
	}

}