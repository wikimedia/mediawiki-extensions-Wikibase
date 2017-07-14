<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;

/**
 * TODO: better name?
 * TODO: does it make sense to have this extend ChangeOpDeserializer interface?
 */
interface PermissionAwareChangeOpDeserializer extends ChangeOpDeserializer {

	/**
	 * @param array $changeRequest
	 * @return bool
	 */
	public function includesChangesToEntityTerms( array $changeRequest );

}
