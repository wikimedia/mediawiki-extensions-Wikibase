<?php

namespace Wikibase\Repo\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Summary;

/**
 * @license GPL-2.0-or-later
 */
class NullChangeOp implements ChangeOp {

	/**
	 * @see ChangeOp::validate
	 *
	 * @param EntityDocument $entity Unused
	 *
	 * @return Result Always valid
	 */
	public function validate( EntityDocument $entity ) {
		return Result::newSuccess();
	}

	/**
	 * @see ChangeOp::apply
	 *
	 * @param EntityDocument $entity Unused
	 * @param Summary|null $summary Unused
	 */
	public function apply( EntityDocument $entity, Summary $summary = null ) {
		// no op
	}

	/**
	 * @see ChangeOp::getActions
	 *
	 * @return string[]
	 */
	public function getActions() {
		return [];
	}

}
