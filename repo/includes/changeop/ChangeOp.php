<?php

namespace Wikibase;

/**
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
interface ChangeOp {

	/**
	 * @since 0.5
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