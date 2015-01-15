<?php

namespace Wikibase;

use ParserOutput;
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
	 * Get the noexternallanglinks page property from the ParserOutput,
	 * which is set by the {{#noexternallanglinks}} parser function.
	 *
	 * @param ParserOutput $out
	 *
	 * @return string[] A list of language codes, identifying which repository links to ignore.
	 *         Empty if {{#noexternallanglinks}} was not used on the page.
	 */
	public static function getNoExternalLangLinks( ParserOutput $out ) {
		$property = $out->getProperty( 'noexternallanglinks' );
		$nel = is_string( $property ) ? unserialize( $property ) : array();
		return $nel;
	}

	/**
	 * Set the noexternallanglinks page property in the ParserOutput,
	 * which is set by the {{#noexternallanglinks}} parser function.
	 *
	 * @since 0.4
	 *
	 * @param ParserOutput $out
	 * @param string[] $noexternallanglinks a list of languages to suppress
	 */
	public static function setNoExternalLangLinks( ParserOutput $out, array $noexternallanglinks ) {
		$out->setProperty( 'noexternallanglinks', serialize( $noexternallanglinks ) );
	}

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
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();

		$namespaceChecker = new NamespaceChecker(
			$settings->getSetting( 'excludeNamespaces' ),
			$settings->getSetting( 'namespaces' )
		);

		if ( !$namespaceChecker->isWikibaseEnabled( $parser->getTitle()->getNamespace() ) ) {
			// shorten out
			return '';
		}

		$langs = func_get_args();
		// Remove the first member, which is the parser.
		array_shift( $langs );

		$output = $parser->getOutput();

		$nel = array_merge( self::getNoExternalLangLinks( $output ), $langs );
		self::setNoExternalLangLinks( $output, $nel );

		return '';
	}

}
