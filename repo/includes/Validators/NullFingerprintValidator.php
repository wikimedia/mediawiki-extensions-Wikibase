<?php

namespace Wikibase\Repo\Validators;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class NullFingerprintValidator implements FingerprintValidator {

	/**
	 * @see FingerprintValidator::validateFingerprint
	 *
	 * @param Fingerprint $fingerprint
	 * @param EntityId $entityId
	 * @param string[]|null $languageCodes
	 *
	 * @return Result
	 */
	public function validateFingerprint(
		Fingerprint $fingerprint,
		EntityId $entityId,
		array $languageCodes = null
	) {
		return Result::newSuccess();
	}

}
