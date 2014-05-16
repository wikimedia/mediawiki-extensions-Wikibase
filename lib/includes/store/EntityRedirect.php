<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Represents a redirect from one EntityId to another.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityRedirect  {

	/**
	 * @var EntityId
	 */
	private $entityId;

	/**
	 * @var EntityId
	 */
	private $targetId;

	/**
	 * @param EntityId $entityId
	 * @param EntityId $targetId
	 */
	function __construct( EntityId $entityId, EntityId $targetId ) {
		$this->entityId = $entityId;
		$this->targetId = $targetId;
	}

	/**
	 * @return EntityId
	 */
	public function getEntityId() {
		return $this->entityId;
	}

	/**
	 * @return EntityId
	 */
	public function getTargetId() {
		return $this->targetId;
	}

	public function equals() {
		...
	}

}
