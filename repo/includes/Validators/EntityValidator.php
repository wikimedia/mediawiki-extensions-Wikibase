<?php

namespace Wikibase\Validators;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\Entity;

/**
 * Validator interface for validating Entities.
 * This is intended for checking global constraints,
 * in particular prior to saving an entity.
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
	 * @param Entity $entity
	 *
	 * @return Result
	 */
	public function validateEntity( Entity $entity );

}