<?php
declare( strict_types=1 );

namespace Wikibase\Lib\FederatedProperties;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @license GPL-2.0-or-later
 */
class FederatedPropertyId implements PropertyId {

	private string $serialization;

	private string $remoteId;

	/**
	 * @param string $uriSerialization The concept URI serialization of the ID
	 * @param string $remoteId The ID as it is referred to on the federation source, e.g. 'P31' for serialization
	 *        'http://www.wikidata.org/entity/P31'.
	 */
	public function __construct( string $uriSerialization, string $remoteId ) {
		self::assertValidSerialization( $uriSerialization );

		$this->serialization = $uriSerialization;
		$this->remoteId = $remoteId;
	}

	public function __serialize(): array {
		return [ $this->serialization ];
	}

	public function __unserialize( array $data ): void {
		[ $serialization ] = $data;
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

	/**
	 * The ID as it is referred to on the federation source, e.g. 'P31' for serialization 'http://www.wikidata.org/entity/P31'.
	 *
	 * This method must only be used when communicating with the federation source, but never to represent a FederatedPropertyId locally,
	 * because removing the concept base URI prefix makes the ID ambiguous.
	 */
	public function getRemoteIdSerialization(): string {
		return $this->remoteId;
	}

	/** @inheritDoc */
	public function getSerialization() {
		return $this->serialization;
	}

	/** @inheritDoc */
	public function __toString() {
		return $this->getSerialization();
	}

	/** @inheritDoc */
	public function equals( $target ) {
		return $target instanceof FederatedPropertyId &&
			$target->getSerialization() === $this->serialization;
	}

}
