<?php

namespace Wikibase\View;

use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @license GPL-2.0-or-later
 */
interface ViewPlaceHolderEmitter {

	public function preparePlaceHolders(
		ParserOutput $parserOutput,
		EntityDocument $entity,
		$languageCode
	);

}
