<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;
use Serializers\Serializer;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\Repo\WikibaseRepo;

class LegacyInternalClaimSerializer implements Serializer {

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

		return WikibaseRepo::getDefaultInstance()->getInternalClaimSerializer()->serialize( $claim );
	}

}
