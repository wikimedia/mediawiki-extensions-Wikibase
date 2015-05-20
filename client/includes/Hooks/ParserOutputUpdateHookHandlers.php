<?php

namespace Wikibase\Client\Hooks;

use Content;
use ParserOutput;
use StubUserLang;
use Title;
use Wikibase\Client\WikibaseClient;
use Wikibase\InterwikiSorter;
use Wikibase\LangLinkHandler;
use Wikibase\NamespaceChecker;

/**
 * @since 0.5.
 *
 * @license GPL 2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class ParserOutputUpdateHookHandlers {

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @var LangLinkHandler
	 */
	private $langLinkHandler;

	/**
	 * @var InterwikiSorter
	 */
	private $interwikiSorter;

	/**
	 * @var bool
	 */
	private $alwaysSort;

	public static function newFromGlobalState() {
		global $wgLang;
		StubUserLang::unstub( $wgLang );

		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();

		$interwikiSorter = new InterwikiSorter(
			$settings->getSetting( 'sort' ),
			$settings->getSetting( 'interwikiSortOrders' ),
			$settings->getSetting( 'sortPrepend' )
		);

		return new ParserOutputUpdateHookHandlers(
			$wikibaseClient->getNamespaceChecker(),
			$wikibaseClient->getLangLinkHandler(),
			$interwikiSorter,
			$settings->getSetting( 'alwaysSort' )
		);
	}

	/**
	 * Static handler for the ContentAlterParserOutput hook.
	 *
	 * @param Content $content
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 */
	public static function onContentAlterParserOutput( Content $content, Title $title, ParserOutput $parserOutput ) {
		// this hook tries to access repo SiteLinkTable
		// it interferes with any test that parses something, like a page or a message
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			return;
		}

		$handler = self::newFromGlobalState();
		$handler->doContentAlterParserOutput( $title, $parserOutput );
	}

	/**
	 * @param NamespaceChecker $namespaceChecker
	 * @param LangLinkHandler $langLinkHandler
	 * @param InterwikiSorter $sorter
	 * @param boolean $alwaysSort
	 */
	public function __construct(
		NamespaceChecker $namespaceChecker,
		LangLinkHandler $langLinkHandler,
		InterwikiSorter $sorter,
		$alwaysSort
	) {

		$this->namespaceChecker = $namespaceChecker;
		$this->langLinkHandler = $langLinkHandler;
		$this->interwikiSorter = $sorter;
		$this->alwaysSort = $alwaysSort;
	}

	/**
	 * Hook runs after internal parsing
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ContentAlterParserOutput
	 *
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 *
	 * @return bool
	 */
	public function doContentAlterParserOutput( Title $title, ParserOutput $parserOutput ) {
		if ( !$this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) ) {
			// shorten out
			return true;
		}

		$useRepoLinks = $this->langLinkHandler->useRepoLinks( $title, $parserOutput );

		if ( $useRepoLinks ) {
			// add links
			$this->langLinkHandler->addLinksFromRepository( $title, $parserOutput );
		}

		$this->langLinkHandler->updateItemIdProperty( $title, $parserOutput );
		$this->langLinkHandler->updateOtherProjectsLinksData( $title, $parserOutput );

		if ( $useRepoLinks || $this->alwaysSort ) {
			$interwikiLinks = $parserOutput->getLanguageLinks();
			$sortedLinks = $this->interwikiSorter->sortLinks( $interwikiLinks );
			$parserOutput->setLanguageLinks( $sortedLinks );
		}

		return true;
	}

}
