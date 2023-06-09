<?php

namespace Wikibase\Repo\ParserOutput;

use InvalidArgumentException;
use ParserOutput;
use Wikibase\Lib\Store\EntityRevision;

/**
 * @license GPL-2.0-or-later
 */
interface EntityParserOutputGenerator {

	/**
	 * Creates the parser output for the given entity revision.
	 *
	 * @throws InvalidArgumentException
	 * @return ParserOutput
	 */
	public function getParserOutput(
		EntityRevision $entityRevision,
		bool $generateHtml = true
	);

}
