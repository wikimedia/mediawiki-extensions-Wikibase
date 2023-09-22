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

	public function __unserialize( array $data ): void {
		$this->serialization = $data['serialization'];
	}

	/**
	 * @return string
	 */
	public function getEntityType() {
		return 'custom';
	}

}
