<?php

namespace Wikibase\DataModel\Services\Statement\Filter;

use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementFilter;

/**
 * An unconditional statement filter that always accepts all statements, and never rejects a
 * statement. This acts as a null implementation in cases where filtering is supported but not
 * needed.
 *
 * @since 3.2
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class NullStatementFilter implements StatementFilter {

	/**
	 * @since 3.3
	 */
	public const FILTER_TYPE = 'null';

	/**
	 * @see StatementFilter::statementMatches
	 *
	 * @param Statement $statement
	 *
	 * @return bool
	 */
	public function statementMatches( Statement $statement ) {
		return true;
	}

}
