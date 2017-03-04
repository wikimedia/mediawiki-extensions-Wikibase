<?php

namespace Wikibase\Client\Hooks;

use Config;
use Content;
use ExtensionRegistry;
use MediaWiki\MediaWikiServices;
use ParserOutput;
use Title;
use Wikibase\Client\WikibaseClient;
use Wikibase\InterwikiSorter;
use Wikibase\NamespaceChecker;
use Wikibase\NoLangLinkHandler;
use Wikibase\SettingsArray;

/**
 * @license GPL-2.0+
 */
class InterwikiSortingHookHandlers {

	/**
	 * @var InterwikiSorter
	 */
	private $interwikiSorter;

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @return self
	 */
	public static function newFromGlobalState() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$namespaceChecker = $wikibaseClient->getNamespaceChecker();

		$config = MediaWikiServices::getInstance()->getMainConfig();

		if (
			$config->has( 'InterwikiSortingSort' ) &&
			$config->has( 'InterwikiSortingInterwikiSortOrders' ) &&
			$config->has( 'InterwikiSortingSortPrepend' )
		) {
			return self::newFromInterwikiSortingConfig( $config, $namespaceChecker );
		}

		$settings = $wikibaseClient->getSettings();

		return self::newFromWikibaseConfig( $settings, $namespaceChecker );
	}

	/**
	 * @param Config $config
	 * @param NamespaceChecker $namespaceChecker
	 *
	 * @return self
	 */
	public static function newFromInterwikiSortingConfig(
		Config $config,
		NamespaceChecker $namespaceChecker
	) {
		$interwikiSorter = new InterwikiSorter(
			$config->get( 'InterwikiSortingSort' ),
			$config->get( 'InterwikiSortingInterwikiSortOrders' ),
			$config->get( 'InterwikiSortingSortPrepend' )
		);

		return new self(
			$interwikiSorter,
			$namespaceChecker
		);
	}

	/**
	 * @param SettingsArray $settings
	 * @param NamespaceChecker $namespaceChecker
	 *
	 * @return self
	 */
	public static function newFromWikibaseConfig(
		SettingsArray $settings,
		NamespaceChecker $namespaceChecker
	) {
		$interwikiSorter = new InterwikiSorter(
			$settings->getSetting( 'sort' ),
			$settings->getSetting( 'interwikiSortOrders' ),
			$settings->getSetting( 'sortPrepend' )
		);

		return new self(
			$interwikiSorter,
			$namespaceChecker
		);
	}

	/**
	 * Static handler for the ContentAlterParserOutput hook.
	 *
	 * @param Content $content
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 *
	 * @return bool
	 */
	public static function onContentAlterParserOutput(
		Content $content,
		Title $title,
		ParserOutput $parserOutput
	) {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'InterwikiSorting' ) ) {
			$handler = self::newFromGlobalState();
			$handler->doContentAlterParserOutput( $title, $parserOutput );
		}

		return true;
	}

	/**
	 * @param InterwikiSorter $sorter
	 * @param NamespaceChecker $namespaceChecker
	 */
	public function __construct(
		InterwikiSorter $sorter,
		NamespaceChecker $namespaceChecker
	) {
		$this->interwikiSorter = $sorter;
		$this->namespaceChecker = $namespaceChecker;
	}

	/**
	 * Hook runs after internal parsing
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ContentAlterParserOutput
	 *
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 *
	 * @return void
	 */
	public function doContentAlterParserOutput( Title $title, ParserOutput $parserOutput ) {
		if ( !$this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) ) {
			return;
		}

		if ( !$this->hasNoExternalLangLinks( $parserOutput ) ) {
			$interwikiLinks = $parserOutput->getLanguageLinks();
			$sortedLinks = $this->interwikiSorter->sortLinks( $interwikiLinks );
			$parserOutput->setLanguageLinks( $sortedLinks );
		}
	}

	/**
	 * @param ParserOutput $parserOutput
	 *
	 * @return bool
	 */
	private function hasNoExternalLangLinks( ParserOutput $parserOutput ) {
		$noExternalLangLinks = NoLangLinkHandler::getNoExternalLangLinks( $parserOutput );

		if ( in_array( '*', $noExternalLangLinks ) ) {
			return true;
		}

		return false;
	}

}
