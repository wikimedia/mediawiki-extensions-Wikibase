<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use ValueValidators\Result;
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
	 * @param array|null $validationErrors
	 */
	public function __construct( EntityId $entityId = null, $isEntityChanged = false, array $validationErrors = null ) {
		$this->isEntityChanged = $isEntityChanged;
		$this->entityId = $entityId;
		$this->validationErrors = $validationErrors;
	}

	public function getEntityId() {
		return $this->entityId;
	}

	public function isEntityChanged() {
		return $this->isEntityChanged;
	}

	public function validate(): Result {
		if ( $this->validationErrors !== null ) {
			return Result::newError( $this->validationErrors );
		} else {
			return Resutl::newSuccess();
		}
	}
}
