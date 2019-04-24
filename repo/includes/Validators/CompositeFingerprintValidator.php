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
	 * @param FingerprintValidator[] $validators
	 */
	public function __construct( array $validators ) {
		$this->validators = $validators;
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
		foreach ( $this->validators as $validator ) {
			$result = $validator->validateFingerprint(
				$labels,
				$descriptions,
				$entityId,
				$languageCodes
			);

			if ( !$result->isValid() ) {
				return $result;
			}
		}

		return Result::newSuccess();
	}

}
