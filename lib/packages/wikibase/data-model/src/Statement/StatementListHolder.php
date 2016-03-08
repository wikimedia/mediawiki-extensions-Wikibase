<?php

namespace Wikibase\DataModel\Statement;

/**
 * Interface for classes that contain a StatementList.
 *
 * @since 3.0
 * @deprecated since 5.1, will be removed in 6.0 in favor of StatementListProvider, which will then
 *  give the guarantee to return an object by reference. Changes to that object change the entity.
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
