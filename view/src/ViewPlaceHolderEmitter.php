<?php

use Wikibase\DataModel\Entity\EntityDocument;

interface ViewPlaceHolderEmitter {

	public function preparePlaceHolders(
		ParserOutput $parserOutput,
		EntityDocument $entity,
		$languageCode
	);

}
