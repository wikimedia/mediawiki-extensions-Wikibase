<?php

namespace Wikibase\DataModel\Statement;

/**
 * Interface for classes that contain a StatementList.
 *
 * @since 3.0
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
