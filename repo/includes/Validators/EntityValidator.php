<?php

namespace Wikibase\Validators;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\Entity;

/**
 * Validator interface for entities. This is used for checking potentially complex constraints.
 *
 * This is essentially a vehicle to introduce knowledge about global state into
 * the context of the save operation.
 *
 * One use case is the pre-save validation in EntityContent, that is, enforcing
 * hard global constraints.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface EntityValidator {

	/**
	 * Validate an entity.
	 *
	 * @since 0.5
	 *
	 * @param Entity $entity
	 *
	 * @return Result
	 */
	public function validateEntity( Entity $entity );

}