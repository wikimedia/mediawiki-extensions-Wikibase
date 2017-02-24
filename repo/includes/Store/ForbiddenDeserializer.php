<?php

namespace Wikibase\Repo\Store;

use Deserializers\Deserializer;
use RuntimeException;

/**
 * @license GPL-2.0+
 */
class ForbiddenDeserializer implements Deserializer {

	public function deserialize( $serialization ) {
		throw new RuntimeException( 'Deserialization is not supported!' );
	}

}
