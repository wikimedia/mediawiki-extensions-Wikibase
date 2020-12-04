<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\ChangeOp\ChangeOpResult;

/**
 * Stub class providing ChangeOpResults for tests
 * @license GPL-2.0-or-later
 */
class ChangeOpResultStub implements ChangeOpResult {

	/** @var bool */
	public $isEntityChanged;
	/** @var EntityId|null */
	public $entityId;

	public function __construct(
		EntityId $entityId = null,
		bool $isEntityChanged = false,
		array $validationErrors = null
	) {
		$this->isEntityChanged = $isEntityChanged;
		$this->entityId = $entityId;
		$this->validationErrors = $validationErrors;
	}

	public function getEntityId(): ?EntityId {
		return $this->entityId;
	}

	public function isEntityChanged(): bool {
		return $this->isEntityChanged;
	}

	public function validate(): Result {
		if ( $this->validationErrors !== null ) {
			return Result::newError( $this->validationErrors );
		} else {
			return Result::newSuccess();
		}
	}
}
