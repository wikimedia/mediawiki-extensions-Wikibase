<?php

namespace Wikibase\Repo;

use Wikibase\DataModel\Services\Statement\Grouper\NullStatementGrouper;
use Wikibase\DataModel\Services\Statement\Grouper\StatementGrouper;

/**
 * Factory for StatementGrouper instances for different entity types.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Daniel Kinzler
 */
class StatementGrouperFactory {

	/**
	 * @param string $entityType
	 *
	 * @return StatementGrouper
	 */
	public function getStatementGrouper( $entityType ) {
		// TODO: Group statements into actual sections, including an identifiers section.
		return new NullStatementGrouper();
	}

}
