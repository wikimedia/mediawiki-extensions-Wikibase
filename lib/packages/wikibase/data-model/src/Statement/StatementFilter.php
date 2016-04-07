<?php

namespace Wikibase\DataModel\Statement;

/**
 * @since 4.1
 *
 * @license GPL-2.0+
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
