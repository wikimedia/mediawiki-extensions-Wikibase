<?php

declare( strict_types = 1 );

namespace Wikibase\Client\ParserOutput;

use MediaWiki\Parser\ParserOutput;

/**
 * @author Arthur Taylor
 * @license GPL-2.0-or-later
 */
class ScopedParserOutputProvider implements ParserOutputProvider {

	private ParserOutput $parserOutput;
	private bool $scopeClosed = false;

	public function __construct( ParserOutput $parserOutput ) {
		$this->parserOutput = $parserOutput;
	}

	/**
	 * @throws \Exception In the case that a call to getParserOutput is made after the
	 * 	       scope for the ParserOutputProvider has been closed.
	 */
	public function getParserOutput(): ParserOutput {
		if ( $this->scopeClosed ) {
			throw new \Exception( "Tried to access parser output beyond expected scope" );
		}
		return $this->parserOutput;
	}

	public function close(): void {
		$this->scopeClosed = true;
	}

}
