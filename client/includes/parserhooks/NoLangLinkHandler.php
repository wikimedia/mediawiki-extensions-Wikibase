<?php

namespace Wikibase;

use Wikibase\Client\WikibaseClient;

/**
 * Handles the NOEXTERNALLANGLINKS parser function.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 *
 * @author Nikola Smolenski <smolensk@eunet.rs>
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class NoLangLinkHandler {

	/**
	 * Parser function
	 *
	 * @since 0.4
	 *
	 * @param \Parser &$parser
	 *
	 * @return string
	 */
	public static function handle( &$parser ) {
		$namespaceChecker = new NamespaceChecker( Settings::get( 'excludeNamespaces' ), Settings::get( 'namespaces' ) );

		if ( !$namespaceChecker->isWikibaseEnabled( $parser->getTitle()->getNamespace() ) ) {
			// shorten out
			return '';
		}

		$langLinkHandler = new LangLinkHandler(
			Settings::get( 'siteGlobalID' ),
			$namespaceChecker,
			WikibaseClient::getDefaultInstance()->getStore()->getSiteLinkTable(),
			\Sites::singleton(),
			WikibaseClient::getDefaultInstance()->getLangLinkSiteGroup()
		);

		$langs = func_get_args();
		// Remove the first member, which is the parser.
		array_shift( $langs );

		$output = $parser->getOutput();

		$langLinkHandler->excludeRepoLangLinks( $output, $langs );

		return '';
	}

}
