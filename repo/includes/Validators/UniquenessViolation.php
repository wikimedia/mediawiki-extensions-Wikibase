<?php

namespace Wikibase\Repo\Validators;

use ValueValidators\Error;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Represents a violation of a uniqueness constraint.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class UniquenessViolation extends Error {

	/**
	 * @var EntityId|null
	 */
	private $conflictingEntity;

	/**
	 * @see Error::__construct()
	 *
	 * @param EntityId|null $conflictingEntity The entity causing the conflict, if known
	 * @param string $text
	 * @param string $code
	 * @param mixed[] $params
	 */
	public function __construct( ?EntityId $conflictingEntity, $text, $code, array $params ) {
		parent::__construct( $text, Error::SEVERITY_ERROR, null, $code, $params );

		$this->conflictingEntity = $conflictingEntity;
	}

	public function getConflictingEntity(): ?EntityId {
		return $this->conflictingEntity;
	}

}
