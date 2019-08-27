<?php


namespace Wikibase\Repo\Tests\ChangeOp;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\ChangeOp\ChangeOpResult;

/**
 * Stub class providing ChangeOpResults for tests
 */
class ChangeOpResultStub implements ChangeOpResult {

	public $isEntityChanged;
	public $entityId;

	/**
	 * @param EntityId|null $entityId
	 * @param bool $isEntityChanged
	 */
	public function __construct( EntityId $entityId = null, $isEntityChanged = false ) {
		$this->isEntityChanged = $isEntityChanged;
		$this->entityId = $entityId;
	}

	public function getEntityId() {
		return $this->entityId;
	}

	public function isEntityChanged() {
		return $this->isEntityChanged;
	}

}
