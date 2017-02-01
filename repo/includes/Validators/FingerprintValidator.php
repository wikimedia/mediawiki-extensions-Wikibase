<?php

namespace Wikibase\Repo\Validators;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\TermList;

/**
 * Validator interface for validating labels, descriptions, and aliases.
 *
 * This is intended particularly for uniqueness checks.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
interface FingerprintValidator {

	/**
	 * @param TermList $labels
	 * @param TermList $descriptions
	 * @param AliasGroupList $aliasGroups
	 * @param EntityId $entityId Context for uniqueness checks. Conflicts with this
	 * entity are ignored.
	 * @param string[]|null $languageCodes If given, the validation is limited to the given
	 * languages. This is intended for optimization for the common case of only a single language
	 * changing.
	 *
	 * @return Result
	 */
	public function validateFingerprint(
		TermList $labels,
		TermList $descriptions,
		AliasGroupList $aliasGroups,
		EntityId $entityId,
		array $languageCodes = null
	);

}
