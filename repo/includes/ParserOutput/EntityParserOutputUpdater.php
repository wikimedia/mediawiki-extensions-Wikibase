<?php

namespace Wikibase\Repo\ParserOutput;

use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @license GPL-2.0-or-later
 */
interface EntityParserOutputUpdater {

	/**
	 * Update extension data, properties or other data in ParserOutput.
	 * These updates are invoked when EntityContent::getParserOutput is called.
	 *
	 * @param ParserOutput $parserOutput
	 * @param EntityDocument $entity
	 *
	 * @return void
	 */
	public function updateParserOutput( ParserOutput $parserOutput, EntityDocument $entity );

}
