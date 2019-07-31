<?php


namespace Wikibase\Repo\ChangeOp;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Class for collection of ChangeOp results
 */
class ChangeOpsResult implements ChangeOpResult {

	private $changeOpsResults;
	private $isEntityChange;
	private $entityId;

	/**
	 * @param EntityId|null $entityId
	 * @param array $changeOpsResults
	 * @param bool $isEntityChange
	 */
	public function __construct( EntityId $entityId = null, $changeOpsResults = [], $isEntityChange = false ) {
		$this->entityId = $entityId;
		$this->changeOpsResults = $changeOpsResults;
		$this->isEntityChange = $isEntityChange;
	}

	public function getChangeOpsResults() {
		return $this->changeOpsResults;
	}

	public function getEntityId() {
		return $this->entityId;
	}

	public function isEntityChanged() {
		foreach ( $this->changeOpsResults as $result ) {
			if ( $result->isEntityChanged() ) {
				return true;
			}
		}

		return false;
	}

}
