<?php

namespace Wikibase\Repo\ParserOutput;

use InvalidArgumentException;
use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;

interface EntityParserOutputGenerator {

	/**
	 * Creates the parser output for the given entity.
	 *
	 * @param EntityDocument $entity
	 * @param bool $generateHtml
	 *
	 * @throws InvalidArgumentException
	 * @return ParserOutput
	 */
	public function getParserOutput(
		EntityDocument $entity,
		$generateHtml = true
	);

}
