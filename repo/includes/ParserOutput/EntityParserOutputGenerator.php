<?php

namespace Wikibase\Repo\ParserOutput;

use InvalidArgumentException;
use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @license GPL-2.0-or-later
 */
interface EntityParserOutputGenerator {

	/**
	 * Creates the parser output for the given entity revision.
	 *
	 * @param EntityDocument $entity
	 * @param int $revision The revision of the entity to render
	 * @param bool $generateHtml
	 *
	 * @throws InvalidArgumentException
	 * @return ParserOutput
	 */
	public function getParserOutput(
		EntityDocument $entity,
		$revision,
		$generateHtml = true
	);

}
