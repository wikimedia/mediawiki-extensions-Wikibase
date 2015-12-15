<?php

namespace Wikibase\Repo\ParserOutput;

use ParserOutput;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
interface ParserOutputDataUpdater {

	/**
	 * Update extension data, properties or other data in ParserOutput.
	 * These updates are invoked when EntityContent::getParserOutput is called.
	 *
	 * @param ParserOutput $parserOutput
	 *
	 * @fixme This should be turned into a flush() method with no parameter. The ParserOutput would
	 * then be a constructor parameter in all implementations of this interface. This also means
	 * that the concrete updaters must be constructed inside of the EntityParserOutputGenerator
	 * class. Otherwise they can not get the ParserOutput during construction time. Or this needs an
	 * additional factory.
	 */
	public function updateParserOutput( ParserOutput $parserOutput );

}
