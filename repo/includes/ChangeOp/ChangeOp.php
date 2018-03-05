<?php

namespace Wikibase\Repo\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Summary;

/**
 * A ChangeOp represents a modification of an entity. It is responsible for
 * the actual modifications, as well as providing associated services such as
 * building an appropriate edit summary and performing validation of the change.
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Daniel Kinzler
 */
interface ChangeOp {

	/**
	 * Returns a list of actions, defined as EntityPermissionChecker::ACTION_ constants,
	 * that the change op involves, so user permissions can be checked accordingly prior
	 * to validating and/or applying the change op.
	 *
	 * @return string[] Array of EntityPermissionChecker::ACTION_ constants
	 */
	public function getActions();

	/**
	 * Validates the current ChangeOp. This indicates whether this ChangeOp is valid
	 * with respect to the given entity.
	 *
	 * @param EntityDocument $entity
	 *
	 * @throws InvalidArgumentException when called with an unexpected entity type.
	 * @throws ChangeOpException when the change is invalid for this entity for other reasons than
	 *  the entity type.
	 * @return Result
	 */
	public function validate( EntityDocument $entity );

	/**
	 * Applies the change represented by this ChangeOp to the given Entity.
	 *
	 * @param EntityDocument $entity
	 * @param Summary|null $summary
	 *
	 * @throws InvalidArgumentException when called with an unexpected entity type.
	 * @throws ChangeOpException when the change can not be applied to the entity, e.g. because the
	 *  same data was edited in the meantime.
	 */
	public function apply( EntityDocument $entity, Summary $summary = null );

}
