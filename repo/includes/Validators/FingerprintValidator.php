<?php

namespace Wikibase\Repo\Validators;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * Validator interface for validating Entity Fingerprints.
 *
 * This is intended particularly for uniqueness checks.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
interface FingerprintValidator {

	/**
	 * Validate the given fingerprint.
	 *
	 * @param Fingerprint $fingerprint
	 * @param EntityId $entityId Context for uniqueness checks. Conflicts with this
	 * entity are ignored.
	 * @param string[]|null $languageCodes If given, the validation is limited to the given
	 * languages. This is intended for optimization for the common case of only a single language
	 * changing.
	 *
	 * @return Result
	 */
	public function validateFingerprint(
		Fingerprint $fingerprint,
		EntityId $entityId,
		array $languageCodes = null
	);

}
