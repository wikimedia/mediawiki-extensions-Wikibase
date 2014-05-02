<?php

namespace Wikibase\Validators;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\Entity;

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
	public function __construct( array $validators ) {
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
	 *
	 * @return Result
	 */
	public function validateEntity( Entity $entity ) {
		foreach ( $this->validators as $validator ) {
			$result = $validator->validateEntity( $entity );

			if ( !$result->isValid() ) {
				return $result;
			}
		}

		return Result::newSuccess();
	}

}
