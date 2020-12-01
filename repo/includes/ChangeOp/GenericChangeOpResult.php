<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Holds only generic info on whether entity was changed or not
 * @license GPL-2.0-or-later
 */
class GenericChangeOpResult implements ChangeOpResult {

	/** @var EntityId|null */
	private $entityId;
	/** @var bool */
	private $isEntityChanged;

	public function __construct( ?EntityId $entityId, bool $isEntityChanged ) {
		$this->entityId = $entityId;
		$this->isEntityChanged = $isEntityChanged;
	}

	public function getEntityId(): ?EntityId {
		return $this->entityId;
	}

	public function isEntityChanged(): bool {
		return $this->isEntityChanged;
	}

	public function validate(): Result {
		return Result::newSuccess();
	}

}
