<?php

namespace Wikibase\Repo\Validators;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * Composite validator for applying multiple validators as one.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class CompositeEntityValidator implements EntityValidator {

	/**
	 * @var EntityValidator[]
	 */
	private $validators;

	/**
	 * @var bool
	 */
	private $failFast;

	/**
	 * @param EntityValidator[] $validators
	 * @param bool $failFast If true, validation will be aborted after the first sub validator fails.
	 */
	public function __construct( array $validators, $failFast = true ) {
		$this->validators = $validators;
		$this->failFast = $failFast;
	}

	/**
	 * Validate an entity by applying each of the validators supplied to the constructor.
	 *
	 * @see EntityValidator::validateEntity
	 *
	 * @param EntityDocument $entity
	 *
	 * @return Result
	 */
	public function validateEntity( EntityDocument $entity ) {
		$result = Result::newSuccess();

		foreach ( $this->validators as $validator ) {
			$subResult = $validator->validateEntity( $entity );

			if ( !$subResult->isValid() ) {
				if ( $this->failFast ) {
					return $subResult;
				} else {
					$result = Result::merge( $result, $subResult );
				}
			}
		}

		return $result;
	}

}
