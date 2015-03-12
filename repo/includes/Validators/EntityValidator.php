<?php

namespace Wikibase\Validators;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * Validator interface used for validating Entities in a global context.
 * This is used to represent global constraints.
 *
 * @see EntityConstraintProivder
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface EntityValidator {

	/**
	 * Validate an entity before saving.
	 * This is intended for enforcing "hard" global constraints.
	 *
	 * @since 0.5
	 *
	 * @param EntityDocument $entity The entity to validate
	 *
	 * @return Result The validation result. Errors in the Result object
	 *         will typically be instances of UniquenessViolation.
	 */
	public function validateEntity( EntityDocument $entity );

}