<?php

namespace Wikibase\Validators;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;

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
	 * @param EntityId $ignoreConflictsWith Ignore conflicts with this entity. Useful when merging
	 *        two entities, and checking constraints for the target entity before updating
	 *        the database removing entries of the source entity.
	 *
	 * @return Result
	 */
	public function validateEntity( Entity $entity, EntityId $ignoreConflictsWith = null );

}