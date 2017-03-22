<?php

namespace Wikibase\DataModel\Fixtures;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Dummy custom EntityId implementation for use with EntityIdValueTest
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class CustomEntityId extends EntityId {

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
	}

	/**
	 * @return string
	 */
	public function getEntityType() {
		return 'custom';
	}

}
