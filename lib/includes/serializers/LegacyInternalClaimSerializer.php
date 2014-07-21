<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;
use Wikibase\DataModel\Claim\Claim;

class LegacyInternalClaimSerializer implements \Serializers\Serializer {

	/**
	 * @see Serializer::getSerialized
	 *
	 * @param Claim $claim
	 *
	 * @throws InvalidArgumentException
	 * @return array
	 */
	public function serialize( $claim ) {
		if ( !( $claim instanceof Claim ) ) {
			throw new InvalidArgumentException( '$claim must be an Claim' );
		}

		return $claim->toArray();
	}

}
