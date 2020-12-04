<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Class for collection of ChangeOp results
 * @license GPL-2.0-or-later
 */
class ChangeOpsResult implements ChangeOpResult {

	/** @var ChangeOpResult[] */
	private $changeOpResults;
	/** @var EntityId|null */
	private $entityId;

	/**
	 * @param EntityId|null $entityId
	 * @param ChangeOpResult[] $changeOpResults
	 */
	public function __construct( EntityId $entityId = null, array $changeOpResults = [] ) {
		$this->entityId = $entityId;
		$this->changeOpResults = $changeOpResults;
	}

	public function getChangeOpsResults(): array {
		return $this->changeOpResults;
	}

	public function getEntityId(): ?EntityId {
		return $this->entityId;
	}

	public function isEntityChanged(): bool {
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
