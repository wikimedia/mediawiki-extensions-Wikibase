<?php

namespace Wikibase\Repo\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * Decorator on ChangeOpsResult for collecting and distinguishing a collection
 * of ChangeOpResult instances on entity fingerprint parts.
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpFingerprintResult extends ChangeOpsResult {

	/** @var ChangeOpsResult */
	private $innerChangeOpsResult;

	/** @var TermValidatorFactory */
	private $termValidatorFactory;

	public function __construct( ChangeOpsResult $changeOpsResult, TermValidatorFactory $termValidatorFactory ) {
		$this->innerChangeOpsResult = $changeOpsResult;
		$this->termValidatorFactory = $termValidatorFactory;
	}

	public function getChangeOpsResults(): array {
		return $this->innerChangeOpsResult->getChangeOpsResults();
	}

	public function getEntityId(): ?EntityId {
		return $this->innerChangeOpsResult->getEntityId();
	}

	public function isEntityChanged(): bool {
		return $this->innerChangeOpsResult->isEntityChanged();
	}

	public function validate(): Result {
		$result = $this->innerChangeOpsResult->validate();

		$fingerprintUniquenessValidator = $this->termValidatorFactory->getFingerprintUniquenessValidator(
			$this->getEntityId()->getEntityType()
		);

		if ( $fingerprintUniquenessValidator !== null ) {
			$result = Result::merge(
				$result,
				$fingerprintUniquenessValidator->validate( $this )
			);
		}

		return $result;
	}

}
