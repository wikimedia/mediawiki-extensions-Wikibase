<?php

namespace Wikibase\Repo\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Each ChangeOp applied will return an instance of ChangeOpResult
 * @see https://gerrit.wikimedia.org/r/#/c/mediawiki/extensions/Wikibase/+/518721/
 * @license GPL-2.0-or-later
 */
interface ChangeOpResult {

	/**
	 * The id of the entity document that the change op was applied to
	 * @return EntityId|null
	 */
	public function getEntityId();

	/**
	 * Whether the entity document was actually changed in any way
	 * as a result of applying the change op to it
	 * @return bool
	 */
	public function isEntityChanged();

	/**
	 * Validate a ChangeOpResult
	 *
	 * Mostly suitable for validations that need to run only on the parts
	 * that have been actually changed on an entity. Example of those are
	 * expensive validations that need to run on db or other external
	 * slower types of stores.
	 *
	 * For simpler and less-expensive validations, {@link ChangeOp::validate}
	 * can be used as well.
	 *
	 * Concrete example:
	 * Checking for uniqueness of terms (fingerprint) across the store is an expensive operation.
	 * It better be checked only on terms that are actually going to change on entity, rather than
	 * validate uniqueness on all terms including the ones that are not changed.
	 *
	 * @return Result
	 */
	public function validate(): Result;

}
