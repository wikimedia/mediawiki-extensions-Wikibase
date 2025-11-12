<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\RemoteEntity;

use InvalidArgumentException;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\WikibaseRepo;

/**
 * EntityId implementation for an entity that lives in a remote Wikibase repository.
 *
 * This does NOT represent a local entity; it is a pointer identified by a
 * concept URI (e.g. "https://www.wikidata.org/entity/Q42").
 */
class RemoteEntityId implements EntityId {

	private string $conceptUri;

	/** @var EntityId|null Lazy-parsed local id derived from the concept URI */
	private ?EntityId $parsedLocalId = null;

	public function __construct( string $conceptUri ) {
		if ( $conceptUri === '' || strpos( $conceptUri, 'http' ) !== 0 ) {
			throw new InvalidArgumentException( 'Concept URI must be an absolute http(s) IRI' );
		}
		$this->conceptUri = $conceptUri;
	}

	public function getConceptUri(): string {
		return $this->conceptUri;
	}

	/**
	 * The underlying local entity id parsed from the concept URI (e.g. "Q42", "P31").
	 */
	public function getLocalEntityId(): EntityId {
		if ( $this->parsedLocalId ) {
			return $this->parsedLocalId;
		}

		$basename = preg_replace( '~^.+/entity/~', '', $this->conceptUri );
		if ( $basename === null || $basename === '' ) {
			throw new RuntimeException( 'Cannot derive local id from concept URI: ' . $this->conceptUri );
		}

		// Prefer stable accessor provided by Wikibase Repo.
		$parser = WikibaseRepo::getEntityIdParser();

		$this->parsedLocalId = $parser->parse( $basename );
		return $this->parsedLocalId;
	}

	public function getEntityType(): string {
		return $this->getLocalEntityId()->getEntityType();
	}

	public function getSerialization(): string {
		// Canonical serialization is the concept URI
		return $this->conceptUri;
	}

	public function equals( $target ) {
		return $target instanceof self
			&& $target->conceptUri === $this->conceptUri;
	}

	public function __toString(): string {
		return $this->getSerialization();
	}

	/**
	 * Magic serialization support required by EntityId.
	 */
	public function __serialize(): array {
		return [ 'conceptUri' => $this->conceptUri ];
	}

	/**
	 * Magic unserialization support required by EntityId.
	 */
	public function __unserialize( array $data ): void {
		$this->conceptUri = (string)( $data['conceptUri'] ?? '' );
		if ( $this->conceptUri === '' ) {
			throw new RuntimeException( 'Invalid data for RemoteEntityId::__unserialize: missing conceptUri' );
		}
		$this->parsedLocalId = null;
	}
}
