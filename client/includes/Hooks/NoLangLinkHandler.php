<?php

namespace Wikibase\Client\Hooks;

use Parser;
use ParserOutput;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\WikibaseClient;

/**
 * Handles the NOEXTERNALLANGLINKS parser function.
 *
 * @license GPL-2.0-or-later
 * @author Nikola Smolenski <smolensk@eunet.rs>
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class NoLangLinkHandler {
	/**
	 * Key used to store data in ParserOutput.  Exported for use by unit tests.
	 * @var string
	 */
	public const EXTENSION_DATA_KEY = 'wikibase-noexternallanglinks';

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * Parser function
	 *
	 * @param Parser $parser
	 * @param string ...$langs Language codes or '*'
	 */
	public static function handle( Parser $parser, ...$langs ) {
		$handler = self::factory();
		$handler->doHandle( $parser, $langs );
	}

	private static function factory(): self {
		return new self( WikibaseClient::getNamespaceChecker() );
	}

	public function __construct( NamespaceChecker $namespaceChecker ) {
		$this->namespaceChecker = $namespaceChecker;
	}

	/**
	 * Get the noexternallanglinks data from the ParserOutput,
	 * which is set by the {{#noexternallanglinks}} parser function.
	 *
	 * @param ParserOutput $parserOutput
	 *
	 * @return string[] A list of language codes, identifying which repository links to ignore.
	 *         Empty if {{#noexternallanglinks}} was not used on the page.
	 */
	public static function getNoExternalLangLinks( ParserOutput $parserOutput ) {
		$property = $parserOutput->getExtensionData(
			self::EXTENSION_DATA_KEY
		);
		if ( $property === null ) {
			// BACKWARD COMPATIBILITY: remove after the ParserCache expires
			// This property used to be stored as a page property, so check
			// there as well.
			$old = $parserOutput->getPageProperty( 'noexternallanglinks' );
			if ( is_string( $old ) ) {
				return unserialize( $old );
			}
		}

		return array_keys( $property ?? [] );
	}

	/**
	 * Append new languages to the noexternallanglinks data in the ParserOutput,
	 * which is set by the {{#noexternallanglinks}} parser function.
	 *
	 * @param ParserOutput $parserOutput
	 * @param string[] $noexternallanglinks a list of languages to suppress
	 */
	public static function appendNoExternalLangLinks( ParserOutput $parserOutput, array $noexternallanglinks ) {
		foreach ( $noexternallanglinks as $lang ) {
			$parserOutput->appendExtensionData(
				self::EXTENSION_DATA_KEY, $lang
			);
		}
	}

	/**
	 * Parser function
	 *
	 * @param Parser $parser
	 * @param string[] $langs
	 *
	 * @return string
	 */
	public function doHandle( Parser $parser, array $langs ) {
		if ( !$this->namespaceChecker->isWikibaseEnabled( $parser->getTitle()->getNamespace() ) ) {
			// shorten out
			return '';
		}

		$parserOutput = $parser->getOutput();
		self::appendNoExternalLangLinks( $parserOutput, $langs );

		return '';
	}

}
