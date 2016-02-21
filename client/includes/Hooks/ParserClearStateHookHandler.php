<?php

namespace Wikibase\Client\Hooks;

use Parser;
use Wikibase\Client\WikibaseClient;

/**
 * @since 0.5.
 *
 * @license GPL 2+
 * @author Marius Hoch < hoo@online.de >
 */
class ParserClearStateHookHandler {

	/**
	 * @param Parser $parser
	 *
	 * @return bool
	 */
	public static function onParserClearState( Parser $parser ) {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$wikibaseClient->getRestrictedEntityLookup()->reset();
	}

}
