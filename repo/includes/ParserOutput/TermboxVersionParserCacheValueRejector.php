<?php

namespace Wikibase\Repo\ParserOutput;

use ParserOptions;
use ParserOutput;

/**
 * @license GPL-2.0-or-later
 */
class TermboxVersionParserCacheValueRejector {
	const TERMBOX_VERSION_KEY = 'termboxVersion';
	private $flag;

	public function __construct( TermboxFlag $flag ) {
		$this->flag = $flag;
	}

	/**
	 * Determines whether we should reject an otherwise valid parser cache value
	 * in order to add a Termbox Version to the ParserOptions and Parser Cache key
	 *
	 * To be used in conjunction with Mediawiki's RejectParserCacheValue hook
	 *
	 * @param ParserOutput $parserValue
	 * @param ParserOptions $parserOptions
	 * @return bool
	 */
	public function keepCachedValue( ParserOutput $parserValue, ParserOptions $parserOptions ) {
		return !$this->flag->shouldRenderTermbox()
			|| in_array( self::TERMBOX_VERSION_KEY, $parserValue->getUsedOptions() )
			|| $parserOptions->getOption( self::TERMBOX_VERSION_KEY ) === null;
	}

}
