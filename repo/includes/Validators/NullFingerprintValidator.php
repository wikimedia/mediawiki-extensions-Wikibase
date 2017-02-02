<?php

namespace Wikibase\Repo\Validators;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class NullFingerprintValidator implements FingerprintValidator {

	/**
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
		return Result::newSuccess();
	}

}
