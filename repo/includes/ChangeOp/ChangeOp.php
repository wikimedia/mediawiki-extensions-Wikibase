<?php

namespace Wikibase\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\Summary;

/**
 * A ChangeOp represents a modification of an entity. It is responsible for
 * the actual modifications, as well as providing associated services such as
 * building an appropriate edit summary and performing validation of the change.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Daniel Kinzler
 */
interface ChangeOp {

	/**
	 * @since 0.5
	 *
	 * Validates the current ChangeOp. This indicates whether this ChangeOp is valid
	 * with respect to the given entity.
	 *
	 * @param Entity $entity
	 *
	 * @return Result
	 */
	public function validate( Entity $entity );

	/**
	 * Applies the change represented by this ChangeOp to the given Entity.
	 *
	 * @since 0.5
	 *
	 * @todo: FIXME: It's unclear when this should return false, if ever.
	 *
	 * @param Entity $entity
	 * @param Summary|null $summary
	 *
	 * @return bool Deprecated, do not rely on this value.
	 *
	 * @throws ChangeOpException
	 */
	public function apply( Entity $entity, Summary $summary = null );

}
