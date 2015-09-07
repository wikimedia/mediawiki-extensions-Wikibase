<?php

namespace Wikibase\ChangeOp;

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
	 * @throws ChangeOpException
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
	 * @throws ChangeOpException
	 */
	public function apply( EntityDocument $entity, Summary $summary = null );

	/**
	 * @return string|null
	 */
	public function getModuleName();

}
