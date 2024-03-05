<?php

declare( strict_types = 1 );

namespace Wikibase\Client\ParserOutput;

use MediaWiki\Parser\ParserOutput;

/**
 * @author Arthur Taylor
 * @license GPL-2.0-or-later
 */
interface ParserOutputProvider {
	public function getParserOutput(): ParserOutput;
}
