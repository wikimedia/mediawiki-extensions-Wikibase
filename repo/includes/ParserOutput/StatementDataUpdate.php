<?php

namespace Wikibase\Repo\ParserOutput;

use Wikibase\DataModel\Statement\Statement;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
interface StatementDataUpdate extends ParserOutputDataUpdate {

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
