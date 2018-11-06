<?php

namespace Wikibase\DataModel\Services\Statement\Grouper;

use Wikibase\DataModel\Statement\StatementList;

/**
 * An unconditional statement grouper that always returns a single group, containing the original,
 * unmodified list of statements, and nothing else.
 *
 * @since 3.2
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class NullStatementGrouper implements StatementGrouper {

	/**
	 * @param StatementList $statements
	 *
	 * @return StatementList[] An associative array, mapping the default group identifier
	 *  "statements" to the unmodified StatementList object.
	 */
	public function groupStatements( StatementList $statements ) {
		return [ 'statements' => $statements ];
	}

}
