<?php

namespace Wikibase\Repo\ParserOutput;

use MediaWiki\Parser\ParserOutput;
use Wikibase\DataModel\Statement\Statement;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
interface StatementDataUpdater {

	/**
	 * Update extension data, properties or other data in ParserOutput.
	 * These updates are invoked when EntityContent::getParserOutput is called.
	 */
	public function updateParserOutput( ParserOutput $parserOutput );

	/**
	 * Extract some data or do processing on a Statement during parsing.
	 *
	 * This method is normally invoked when processing a StatementList
	 * for all Statements on a StatementListProvider (e.g. an Item).
	 */
	public function processStatement( Statement $statement );

}
