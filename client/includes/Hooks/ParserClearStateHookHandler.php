<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use MediaWiki\Hook\ParserClearStateHook;
use Parser;
use Wikibase\Client\WikibaseClient;

/**
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class ParserClearStateHookHandler implements ParserClearStateHook {

	/**
	 * Called when resetting the state of the Parser between parses.
	 *
	 * @param Parser $parser
	 *
	 * @return bool
	 */
	public function onParserClearState( $parser ) {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		// Reset the entity access limits, per T127462
		$wikibaseClient->getRestrictedEntityLookup()->reset();

		return true;
	}

}
