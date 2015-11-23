<?php

namespace Wikibase\DataModel\Services\Statement\Grouper;

use Wikibase\DataModel\Statement\StatementList;

/**
 * @since 3.2
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
interface StatementGrouper {

	/**
	 * @param StatementList $statements
	 *
	 * @return StatementList[] All statements from the provided list must be present in the result
	 *  exactly once, and no other statement can be included. The array keys can be used as message
	 *  keys.
	 */
	public function groupStatements( StatementList $statements );

}
