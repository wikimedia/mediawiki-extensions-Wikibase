<?php

namespace Wikibase\DataModel\Services\Statement\Grouper;

use Wikibase\DataModel\Statement\StatementList;

/**
 * @since 3.2
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
interface StatementGrouper {

	/**
	 * @param StatementList $statements
	 *
	 * @return StatementList[] An associative array of StatementList objects. All statements from
	 *  the provided list must be present in the result exactly once, and no other statement can be
	 *  included. The array keys act as identifiers for each group, and can be used as (parts of)
	 *  message keys.
	 */
	public function groupStatements( StatementList $statements );

}
