<?php

namespace Wikibase\Repo\DataUpdates;

use Wikibase\DataModel\Snak\Snak;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
interface SnakDataUpdate extends ParserOutputDataUpdate {

	/**
	 * Extract some data or do processing on a Snak, during parsing.
	 *
	 * This is called method is normally called when processing an
	 * array of all Snaks of and Item or Property.
	 *
	 * @param Snak $snak
	 */
	public function processSnak( Snak $snak );

}
