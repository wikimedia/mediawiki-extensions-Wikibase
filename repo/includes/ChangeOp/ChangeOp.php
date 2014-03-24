<?php

namespace Wikibase\ChangeOp;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\Summary;

/**
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
interface ChangeOp {

	/**
	 * @since 0.5
	 *
	 * @todo: FIXME: It's unclear when this should return false, if ever.
	 *
	 * @param Entity $entity
	 * @param Summary|null $summary
	 *
	 * @return bool
	 *
	 * @throws ChangeOpException
	 */
	public function apply( Entity $entity, Summary $summary = null );

}
