<?php

namespace Wikibase\Repo\ChangeOp;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Each ChangeOp applied will return an instance of ChangeOpResult
 * @see https://gerrit.wikimedia.org/r/#/c/mediawiki/extensions/Wikibase/+/518721/
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

}
