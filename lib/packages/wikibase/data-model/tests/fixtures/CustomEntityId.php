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

	/**
	 * @see Serializable::serialize
	 *
	 * @return string
	 */
	public function serialize() {
		return $this->serialization;
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
