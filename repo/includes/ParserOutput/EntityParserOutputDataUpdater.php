<?php

namespace Wikibase\Repo\ParserOutput;

use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @license GPL-2.0-or-later
 */
interface EntityParserOutputDataUpdater extends ParserOutputDataUpdater {

	public function processEntity( EntityDocument $entity );

}
