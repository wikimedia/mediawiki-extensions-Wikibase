<?php

namespace Wikibase\Repo\ParserOutput;

use ParserOutput;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
interface ParserOutputDataUpdate {

	/**
	 * Update extension data, properties or other data in ParserOutput.
	 * These updates are invoked when EntityContent::getParserOutput is called.
	 *
	 * @param ParserOutput $parserOutput
	 */
	public function updateParserOutput( ParserOutput $parserOutput );

}
