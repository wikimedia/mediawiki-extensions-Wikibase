<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;
use OutOfBoundsException;
use Wikibase\Claims;

/**
 * Serializer for lists of claims.
 *
 * See docs/json.wiki for details of the format.
 *
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimsSerializer extends SerializerObject implements Unserializer {

	/**
	 * @see ApiSerializer::getSerialized
	 *
	 * @since 0.2
	 *
	 * @param mixed $claims
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public function getSerialized( $claims ) {
		if ( !( $claims instanceof Claims ) ) {
			throw new InvalidArgumentException( 'ClaimsSerializer can only serialize Claims objects' );
		}

		//NOTE: when changing the serialization structure, update docs/json.wiki too!

		$claimSerializer = new ClaimSerializer( $this->options );

		if( in_array( 'claims', $this->options->getOption( SerializationOptions::OPT_GROUP_BY_PROPERTIES ) ) ){
			$listSerializer = new ByPropertyListSerializer( 'claim', $claimSerializer, $this->options );
		} else {
			$listSerializer = new ListSerializer( 'claim', $claimSerializer, $this->options );
		}

		return $listSerializer->getSerialized( $claims );
	}

	/**
	 * @see Unserializer::newFromSerialization
	 *
	 * @since 0.5
	 *
	 * @param mixed $serialization
	 *
	 * @return Claims
	 * @throws InvalidArgumentException
	 * @throws OutOfBoundsException
	 */
	public function newFromSerialization( array $serialization ) {
		$claimSerializer = new ClaimSerializer( $this->options );

		if( $this->isAssociative( $serialization ) ){
			$unserializer = new ByPropertyListUnserializer( $claimSerializer );
		} else {
			$unserializer = new ListUnserializer( $claimSerializer );
		}

		return new Claims( $unserializer->newFromSerialization( $serialization ) );
	}

}
