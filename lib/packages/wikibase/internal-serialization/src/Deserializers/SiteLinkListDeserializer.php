<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Exception;
use Wikibase\DataModel\SiteLink;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteLinkListDeserializer implements Deserializer {

	/**
	 * @param mixed $serialization
	 *
	 * @return SiteLink[]
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		$this->assertStructureIsValid( $serialization );
	}

	/**
	 * @since 1.0
	 *
	 * @param mixed $serialization
	 *
	 * @return boolean
	 */
	public function isDeserializerFor( $serialization ) {
		try {
			$this->assertStructureIsValid( $serialization );
			return true;
		}
		catch ( Exception $e ) {
			return false;
		}
	}

	private function assertStructureIsValid( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'SiteLink list serializations should be arrays' );
		}
	}

}