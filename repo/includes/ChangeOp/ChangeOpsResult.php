<?php

namespace Wikibase\Repo\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Class for collection of ChangeOp results
 */
class ChangeOpsResult implements ChangeOpResult {

	private $changeOpResults;
	private $entityId;

	/**
	 * @param EntityId|null $entityId
	 * @param ChangeOpResult[] $changeOpResults
	 */
	public function __construct( EntityId $entityId = null, array $changeOpResults = [] ) {
		$this->entityId = $entityId;
		$this->changeOpResults = $changeOpResults;
	}

	public function getChangeOpsResults() {
		return $this->changeOpResults;
	}

	public function getEntityId() {
		return $this->entityId;
	}

	public function isEntityChanged() {
		foreach ( $this->changeOpResults as $result ) {
			if ( $result->isEntityChanged() ) {
				return true;
			}
		}

		return false;
	}

	public function validate(): Result {
		$finalResult = Result::newSuccess();

		foreach ( $this->changeOpResults as $result ) {
			$finalResult = Result::merge( $finalResult, $result->validate() );
		}

		return $finalResult;
	}

}
