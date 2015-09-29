<?php

namespace Wikibase\Repo\DataUpdates;

use Wikibase\DataModel\Statement\Statement;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
interface StatementDataUpdate extends ParserOutputDataUpdate {

	/**
	 * Extract some data or do processing on a Statement, during parsing.
	 *
	 * This is called method is normally called when processing an
	 * a StatementList for all Statements on an Item or Property.
	 *
	 * @param Statement $statement
	 */
	public function processStatement( Statement $statement );

}
