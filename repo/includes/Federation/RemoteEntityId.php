<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Federation;

use InvalidArgumentException;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * EntityId implementation for an entity that lives in a remote Wikibase repository.
 *
 * This does NOT represent a local entity; it is a pointer of the form
 * (repositoryName, localEntityId), e.g. ("wd", "Q42") or ("wd", "P123").
 */
class RemoteEntityId implements EntityId {

	private string $repositoryName;

	private EntityId $localEntityId;

	public function __construct( string $repositoryName, EntityId $localEntityId ) {
		if ( $repositoryName === '' ) {
			throw new InvalidArgumentException( 'Repository name must not be empty' );
		}

		$this->repositoryName = $repositoryName;
		$this->localEntityId = $localEntityId;
	}

	public function getRepositoryName(): string {
		return $this->repositoryName;
	}

	/**
	 * The underlying un-namespaced entity id (e.g. "Q42", "P31").
	 */
	public function getLocalEntityId(): EntityId {
		return $this->localEntityId;
	}

	public function getEntityType(): string {
		// Delegate to the wrapped id; works for items, properties, etc.
		return $this->localEntityId->getEntityType();
	}

	public function getSerialization(): string {
		// Namespaced serialization, e.g. "wd:Q42" or "wd:P31"
		return $this->repositoryName . ':' . $this->localEntityId->getSerialization();
	}

	public function equals( $target ) {
		return $target instanceof self
			&& $target->repositoryName === $this->repositoryName
			&& $target->localEntityId->equals( $this->localEntityId );
	}

	public function __toString(): string {
		return $this->getSerialization();
	}

	/**
	 * Magic serialization support required by EntityId.
	 *
	 * We just store the repo name and the wrapped EntityId object. PHP will
	 * handle (de)serializing the wrapped EntityId using its own logic.
	 */
	public function __serialize(): array {
		return [
			'repositoryName' => $this->repositoryName,
			'localEntityId' => $this->localEntityId,
		];
	}

	/**
	 * Magic unserialization support required by EntityId.
	 *
	 * We expect localEntityId to already be an EntityId instance
	 * (ItemId, PropertyId, etc.) reconstructed by PHP.
	 */
	public function __unserialize( array $data ): void {
		$this->repositoryName = $data['repositoryName'] ?? '';

		if ( !isset( $data['localEntityId'] ) || !$data['localEntityId'] instanceof EntityId ) {
			throw new RuntimeException(
				'Invalid data for RemoteEntityId::__unserialize: missing or invalid localEntityId'
			);
		}

		$this->localEntityId = $data['localEntityId'];
	}
}
