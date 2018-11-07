<?php

namespace Wikibase\DataModel\Statement;

/**
 * Interface for classes that contain a StatementList.
 *
 * @since 3.0
 * @deprecated since 5.1, will be removed in favor of StatementListProvider, which
 *  gives the guarantee to return an object by reference. Changes to that object change the entity.
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
interface StatementListHolder extends StatementListProvider {

	public function setStatements( StatementList $statements );

}
