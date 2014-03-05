<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\MissingAttributeException;
use Exception;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;

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

		return new SiteLinkList( array() );
	}

	private function assertStructureIsValid( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'SiteLink list serializations should be arrays' );
		}

		foreach ( $serialization as $key => $arrayElement ) {
			$this->assertKeyIsValid( $key );
			$this->assertElementIsValid( $arrayElement );
		}
	}

	private function assertKeyIsValid( $key ) {
		if ( !is_string( $key ) ) {
			throw new DeserializationException( 'All array keys should be strings' );
		}
	}

	private function assertElementIsValid( $arrayElement ) {
		if ( !is_string( $arrayElement ) && !is_array( $arrayElement ) ) {
			throw new DeserializationException( 'All array elements should be of type string or array' );
		}

		if ( is_array( $arrayElement ) ) {
			$this->assertElementIsValidArray( $arrayElement );
		}
	}

	private function assertElementIsValidArray( array $arrayElement ) {
		if ( !array_key_exists( 'name', $arrayElement ) ) {
			throw new MissingAttributeException( 'name' );
		}

		if ( !array_key_exists( 'badges', $arrayElement ) ) {
			throw new MissingAttributeException( 'badges' );
		}
	}

}