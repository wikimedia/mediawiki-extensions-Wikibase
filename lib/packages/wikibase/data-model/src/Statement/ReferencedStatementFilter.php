<?php

namespace Wikibase\DataModel\Statement;

/**
 * A filter that only accepts statements with one or more references, and rejects all unreferenced
 * statements.
 *
 * @since 4.1
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ReferencedStatementFilter implements StatementFilter {

	/**
	 * @since 4.4
	 */
	const FILTER_TYPE = 'referenced';

	/**
	 * @param Statement $statement
	 *
	 * @return bool
	 */
	public function statementMatches( Statement $statement ) {
		return !$statement->getReferences()->isEmpty();
	}

}
