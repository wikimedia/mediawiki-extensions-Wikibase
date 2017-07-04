<?php

namespace Wikibase\Client\Hooks;

use Parser;
use ParserOutput;
use Wikibase\Client\WikibaseClient;
use Wikibase\Client\NamespaceChecker;

/**
 * Handles the NOEXTERNALLANGLINKS parser function.
 *
 * @license GPL-2.0+
 * @author Nikola Smolenski <smolensk@eunet.rs>
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class NoLangLinkHandler {

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * Parser function
	 *
	 * @param Parser &$parser
	 * @param string [$languageCode,...] Language codes or '*'
	 *
	 * @return string
	 */
	public static function handle( &$parser /*...*/ ) {
		$langs = func_get_args();

		// Remove the first member, which is the parser.
		array_shift( $langs );

		$handler = self::newFromGlobalState();
		$handler->doHandle( $parser, $langs );
	}

	/**
	 * @return self
	 */
	private static function newFromGlobalState() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();

		$namespaceChecker = new NamespaceChecker(
			$settings->getSetting( 'excludeNamespaces' ),
			$settings->getSetting( 'namespaces' )
		);

		return new self( $namespaceChecker );
	}

	public function __construct( NamespaceChecker $namespaceChecker ) {
		$this->namespaceChecker = $namespaceChecker;
	}

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

		return is_string( $property ) ? unserialize( $property ) : [];
	}

	/**
	 * Set the noexternallanglinks page property in the ParserOutput,
	 * which is set by the {{#noexternallanglinks}} parser function.
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
	 * @param Parser &$parser
	 * @param string[] $langs
	 *
	 * @return string
	 */
	public function doHandle( &$parser, array $langs ) {
		if ( !$this->namespaceChecker->isWikibaseEnabled( $parser->getTitle()->getNamespace() ) ) {
			// shorten out
			return '';
		}

		$output = $parser->getOutput();
		$nel = array_merge( self::getNoExternalLangLinks( $output ), $langs );

		self::setNoExternalLangLinks( $output, $nel );

		return '';
	}

}
