<?php

namespace Wikibase\DataModel\Fixtures;

use Wikibase\DataModel\Entity\SerializableEntityId;

/**
 * Dummy custom EntityId implementation for use with EntityIdValueTest
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class CustomEntityId extends SerializableEntityId {

	public function __serialize(): array {
		return [ 'serialization' => $this->serialization ];
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @return string
	 */
	public function serialize() {
		return $this->serialization;
	}

	public function __unserialize( array $data ): void {
		$this->serialization = $data['serialization'];
		$this->repositoryName = '';
		$this->localPart = $data['serialization'];
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @param string $serialized
	 */
	public function unserialize( $serialized ) {
		$this->serialization = $serialized;
		$this->repositoryName = '';
		$this->localPart = $serialized;
	}

	/**
	 * @return string
	 */
	public function getEntityType() {
		return 'custom';
	}

}
