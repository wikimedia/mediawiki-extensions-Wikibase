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
	 * The change op has been initialized and ready to be applied. {@link self::apply()} has not been called yet.
	 */
	const STATE_NOT_APPLIED = 'not_applied';

	/**
	 * The change op has applied changes to the {@link EntityDocument} as a result of calling {@link self::apply()}.
	 */
	const STATE_DOCUMENT_CHANGED = 'document_changed';

	/**
	 * The change op has detected that the {@link EntityDocument} is up-to-date with the change, and has not applied
	 * any changes to it after calling {@link self::apply()}.
	 */
	const STATE_DOCUMENT_NOT_CHANGED = 'document_not_changed';

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

	/**
	 * @return string one of:
	 * 	{@link self::STATE_NOT_APPLIED}, {@link self::STATE_DOCUMENT_CHANGED} or {@link self::STATE_DOCUMENT_NOT_CHANGED}
	 */
	public function getState();

}
