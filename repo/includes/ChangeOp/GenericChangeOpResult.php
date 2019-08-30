<?php

namespace Wikibase\Repo\ChangeOp;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Holds only generic info on whether entity was changed or not
 */
class GenericChangeOpResult implements ChangeOpResult {

	private $entityId;
	private $isEntityChanged;

	public function __construct( EntityId $entityId = null, $isEntityChanged ) {
		$this->entityId = $entityId;
		$this->isEntityChanged = $isEntityChanged;
	}

	public function getEntityId() {
		return $this->entityId;
	}

	public function isEntityChanged() {
		return $this->isEntityChanged;
	}

}
