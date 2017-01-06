<?php

namespace Wikibase\Repo\ParserOutput;

use Wikibase\DataModel\Statement\Statement;

/**
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
interface StatementDataUpdater extends ParserOutputDataUpdater {

	/**
	 * Extract some data or do processing on a Statement during parsing.
	 *
	 * This method is normally invoked when processing a StatementList
	 * for all Statements on a StatementListProvider (e.g. an Item).
	 *
	 * @param Statement $statement
	 */
	public function processStatement( Statement $statement );

}
