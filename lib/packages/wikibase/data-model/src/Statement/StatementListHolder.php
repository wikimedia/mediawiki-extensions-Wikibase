<?php

namespace Wikibase\DataModel\Statement;

/**
 * Interface for classes that contain a StatementList.
 *
 * @since 3.0
 * @deprecated since 5.1
 *
 * @license GNU GPL v2+
 * @author Thiemo Mättig
 */
interface StatementListHolder extends StatementListProvider {

	/**
	 * @param StatementList $statements
	 */
	public function setStatements( StatementList $statements );

}
