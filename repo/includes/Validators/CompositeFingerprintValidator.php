<?php

namespace Wikibase\Repo\Validators;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\TermList;

/**
 * Composite validator for applying multiple validators as one.
 *
 * @license GPL-2.0-or-later
 */
class CompositeFingerprintValidator implements FingerprintValidator {

	/**
	 * @var FingerprintValidator[]
	 */
	private $validators;

	/**
	 * @var bool
	 */
	private $failFast;

	/**
	 * @param FingerprintValidator[] $validators
	 * @param bool $failFast If true, validation will be aborted after the first sub validator fails.
	 */
	public function __construct( array $validators, $failFast = true ) {
		$this->validators = $validators;
		$this->failFast = $failFast;
	}

	/**
	 * Validate a fingerprint by applying each of the validators supplied to the constructor.
	 *
	 * @see FingerprintValidator::validateFingerprint
	 *
	 * @param TermList $labels
	 * @param TermList $descriptions
	 * @param EntityId $entityId
	 * @param string[]|null $languageCodes
	 *
	 * @return Result
	 */
	public function validateFingerprint(
		TermList $labels,
		TermList $descriptions,
		EntityId $entityId,
		array $languageCodes = null
	) {
		$result = Result::newSuccess();

		foreach ( $this->validators as $validator ) {
			$subResult = $validator->validateFingerprint(
				$labels,
				$descriptions,
				$entityId,
				$languageCodes
			);

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
