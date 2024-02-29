<?php

declare( strict_types = 1 );

namespace Wikibase\Client\ParserOutput;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;

/**
 * @author Arthur Taylor
 * @license GPL-2.0-or-later
 */
class ParserWrappingParserOutputProvider implements ParserOutputProvider {

	private Parser $parser;

	public function __construct( Parser $parser ) {
		$this->parser = $parser;
	}

	public function getParserOutput(): ParserOutput {
		return $this->parser->getOutput();
	}

}
