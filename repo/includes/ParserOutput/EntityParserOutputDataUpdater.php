<?php

namespace Wikibase\Repo\ParserOutput;

use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @license GPL-2.0-or-later
 */
interface EntityParserOutputDataUpdater {

	public function updateParserOutput( ParserOutput $parserOutput, EntityDocument $entity );

}
