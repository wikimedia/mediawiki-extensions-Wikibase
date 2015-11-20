<?php

namespace Wikibase\DataModel\Services\Statement\Filter;

use Wikibase\DataModel\Statement\Statement;

/**
 * @since 3.2
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class NullStatementFilter implements StatementFilter {

	/**
	 * @see StatementFilter::isMatch
	 *
	 * @param Statement $statement
	 *
	 * @return bool
	 */
	public function isMatch( Statement $statement ) {
		return true;
	}

}
