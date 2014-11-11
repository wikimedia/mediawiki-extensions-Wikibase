<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;
use OutOfBoundsException;
use Wikibase\DataModel\Claim\Claims;

/**
 * Serializer for lists of claims.
 *
 * See docs/json.wiki for details of the format.
 *
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class ClaimsSerializer extends SerializerObject implements Unserializer {

	/**
	 * @var ClaimSerializer
	 */
	protected $claimSerializer;

	/**
	 * @param ClaimSerializer $claimSerializer
	 * @param SerializationOptions $options
	 */
	public function __construct( ClaimSerializer $claimSerializer, SerializationOptions $options = null ) {
		parent::__construct( $options );

		$this->claimSerializer = $claimSerializer;
	}

	/**
	 * @see ApiSerializer::getSerialized
	 *
	 * @since 0.2
	 *
	 * @param Claims $claims
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public function getSerialized( $claims ) {
		if ( !( $claims instanceof Claims ) ) {
			throw new InvalidArgumentException( 'ClaimsSerializer can only serialize Claims objects' );
		}

		//NOTE: when changing the serialization structure, update docs/json.wiki too!

		if( in_array( 'claims', $this->options->getOption( SerializationOptions::OPT_GROUP_BY_PROPERTIES ) ) ){
			$listSerializer = new ByPropertyListSerializer( 'claim', $this->claimSerializer, $this->options );
		} else {
			$listSerializer = new ListSerializer( 'claim', $this->claimSerializer, $this->options );
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
		if( $this->isAssociative( $serialization ) ){
			$unserializer = new ByPropertyListUnserializer( $this->claimSerializer );
		} else {
			$unserializer = new ListUnserializer( $this->claimSerializer );
		}

		return new Claims( $unserializer->newFromSerialization( $serialization ) );
	}

}
