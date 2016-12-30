<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Summary;

/**
 * A ChangeOp represents a modification of an entity. It is responsible for
 * the actual modifications, as well as providing associated services such as
 * building an appropriate edit summary and performing validation of the change.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Addshore
 * @author Daniel Kinzler
 */
interface ChangeOp {

	/**
	 * @since 0.5
	 *
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
	 * @since 0.5
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
