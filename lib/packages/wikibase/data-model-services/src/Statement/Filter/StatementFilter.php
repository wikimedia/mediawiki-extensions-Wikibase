<?php

namespace Wikibase\DataModel\Services\Statement\Filter;

use Wikibase\DataModel\Statement\Statement;

/**
 * @since 3.2
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
interface StatementFilter {

	/**
	 * @param Statement $statement
	 *
	 * @return bool
	 */
	public function isMatch( Statement $statement );

}
