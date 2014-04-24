<?php

namespace Wikibase\Validators;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Composite validator for applying multiple validators as one.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class CompositeEntityValidator implements EntityValidator {

	/**
	 * @var EntityValidator[]
	 */
	protected $validators;

	/**
	 * @param EntityValidator[] $validators
	 */
	function __construct( array $validators ) {
		$this->validators = $validators;
	}

	/**
	 * Validate an entity by applying each of the validators supplied to the constructor.
	 *
	 * @see EntityValidator::validateEntity
	 *
	 * @since 0.5
	 *
	 * @param Entity $entity
	 * @param EntityId $ignoreConflictsWith
	 *
	 * @return Result
	 */
	public function validateEntity( Entity $entity, EntityId $ignoreConflictsWith = null ) {
		foreach ( $this->validators as $validator ) {
			$result = $validator->validateEntity( $entity, $ignoreConflictsWith );

			if ( !$result->isValid() ) {
				return $result;
			}
		}

		return Result::newSuccess();
	}

}