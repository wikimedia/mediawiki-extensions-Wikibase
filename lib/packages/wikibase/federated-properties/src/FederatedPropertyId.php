<?php
declare( strict_types=1 );

namespace Wikibase\Lib\FederatedProperties;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @license GPL-2.0-or-later
 */
class FederatedPropertyId extends PropertyId {

	public function __construct( $serialization ) {
		self::assertValidSerialization( $serialization );
		$this->serialization = $serialization;
	}

	public function serialize(): ?string {
		return $this->serialization;
	}

	public function unserialize( $serialization ): void {
		self::assertValidSerialization( $serialization );
		$this->serialization = $serialization;
	}

	public function getEntityType(): string {
		return Property::ENTITY_TYPE;
	}

	private static function assertValidSerialization( string $serialization ): void {
		if ( filter_var( $serialization, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED ) === false ) {
			throw new InvalidArgumentException( 'FederatedPropertyId Serialization should be a URI with a path' );
		}
	}
}
