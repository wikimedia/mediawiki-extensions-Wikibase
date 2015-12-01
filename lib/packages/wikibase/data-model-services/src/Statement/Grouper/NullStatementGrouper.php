<?php

namespace Wikibase\DataModel\Services\Statement\Grouper;

use Wikibase\DataModel\Statement\StatementList;

/**
 * @since 3.2
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class NullStatementGrouper implements StatementGrouper {

	/**
	 * @param StatementList $statements
	 *
	 * @return StatementList[] An associative array, mapping the default group identifier
	 *  "statements" to the unmodified StatementList object.
	 */
	public function groupStatements( StatementList $statements ) {
		return array( 'statements' => $statements );
	}

}
