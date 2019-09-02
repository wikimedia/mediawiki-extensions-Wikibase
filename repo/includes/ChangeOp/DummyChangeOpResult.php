<?php

namespace Wikibase\Repo\ChangeOp;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Class DummyChangeOpResult has no result
 */
class DummyChangeOpResult implements ChangeOpResult {

	private $entityId;

	public function __construct( EntityId $entityId = null ) {
		 $this->entityId = $entityId;
	}

	public function getEntityId() {
		return $this->entityId;
	}

	public function isEntityChanged() {
		 return false;
	}

}
