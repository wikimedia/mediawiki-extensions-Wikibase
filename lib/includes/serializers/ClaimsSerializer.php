<?php

namespace Wikibase\Lib\Serializers;
use MWException;

/**
 * Serializer for lists of claims.
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
class ClaimsSerializer extends SerializerObject {

	/**
	 * @see ApiSerializer::getSerialized
	 *
	 * @since 0.2
	 *
	 * @param mixed $claims
	 *
	 * @return array
	 * @throws MWException
	 */
	public function getSerialized( $claims ) {
		if ( !( $claims instanceof \Wikibase\Claims ) ) {
			throw new MWException( 'ClaimsSerializer can only serialize Claims objects' );
		}

		//NOTE: when changing the serialization structure, update docs/json.wiki too!

		$claimSerializer = new ClaimSerializer( $this->options );
		$serializer = new ByPropertyListSerializer( 'claim', $claimSerializer, $this->options );

		return $serializer->getSerialized( $claims );
	}

}