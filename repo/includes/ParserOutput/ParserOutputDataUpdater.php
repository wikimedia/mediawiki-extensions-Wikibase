<?php

namespace Wikibase\Repo\ParserOutput;

use ParserOutput;

/**
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
interface ParserOutputDataUpdater {

	/**
	 * Update extension data, properties or other data in ParserOutput.
	 * These updates are invoked when EntityContent::getParserOutput is called.
	 *
	 * @param ParserOutput $parserOutput
	 */
	public function updateParserOutput( ParserOutput $parserOutput );

}
