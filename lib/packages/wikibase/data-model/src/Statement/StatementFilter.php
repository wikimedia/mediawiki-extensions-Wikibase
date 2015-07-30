<?php

namespace Wikibase\DataModel\Statement;

/**
 * @since 4.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface StatementFilter {

	/**
	 * @param Statement $statement
	 *
	 * @return boolean
	 */
	public function statementMatches( Statement $statement );

}
