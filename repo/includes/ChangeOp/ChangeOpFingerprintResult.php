<?php

namespace Wikibase\Repo\ChangeOp;

use ValueValidators\Result;

/**
 * Decorator on ChangeOpsResult for collecting and distinguishing a collection
 * of ChangeOpResult instances on entity fingerprint parts.
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpFingerprintResult extends ChangeOpsResult {

	/** @var ChangeOpsResult */
	private $innerChangeOpsResult;

	public function __construct( ChangeOpsResult $changeOpsResult ) {
		$this->innerChangeOpsResult = $changeOpsResult;
	}

	public function getChangeOpsResults() {
		return $this->innerChangeOpsResult->getChangeOpsResults();
	}

	public function getEntityId() {
		return $this->innerChangeOpsResult->getEntityId();
	}

	public function isEntityChanged() {
		return $this->innerChangeOpsResult->isEntityChanged();
	}

	public function validate(): Result {
		return $this->innerChangeOpsResult->validate();
	}

}
