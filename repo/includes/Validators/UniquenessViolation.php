<?php

namespace Wikibase\Repo\Validators;

use ValueValidators\Error;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Represents a violation of a uniqueness constraint.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class UniquenessViolation extends Error {

	/**
	 * @var EntityId
	 */
	private $conflictingEntity;

	/**
	 * @see Error::__construct()
	 *
	 * @param EntityId $conflictingEntity The entity causing the conflict
	 * @param string $text
	 * @param string $code
	 * @param mixed[] $params
	 */
	public function __construct( EntityId $conflictingEntity, $text, $code, array $params ) {
		parent::__construct( $text, Error::SEVERITY_ERROR, null, $code, $params );

		$this->conflictingEntity = $conflictingEntity;
	}

	/**
	 * @return EntityId
	 */
	public function getConflictingEntity() {
		return $this->conflictingEntity;
	}

}
