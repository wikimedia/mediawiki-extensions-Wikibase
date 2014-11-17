<?php

namespace Wikibase\Client\Hooks;

use Exception;
use OutputPage;
use Parser;
use ParserOutput;
use StripState;
use StubUserLang;
use Title;
use Wikibase\Client\WikibaseClient;
use Wikibase\InterwikiSorter;
use Wikibase\LangLinkHandler;
use Wikibase\NamespaceChecker;
use Wikibase\NoLangLinkHandler;
use Wikibase\SettingsArray;

/**
 * @since 0.5.
 *
 * @license GPL 2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class ParserAfterParseHookHandler {

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

		return new ParserAfterParseHookHandler(
			$wikibaseClient->getNamespaceChecker(),
			$wikibaseClient->getLangLinkHandler(),
			$interwikiSorter,
			$settings->getSetting( 'alwaysSort' )
		);
	}

	/**
	 * Static handler for the ParserAfterParse hook.
	 *
	 * @param Parser|null &$parser
	 * @param string|null &$text Unused.
	 * @param StripState|null $stripState Unused.
	 *
	 * @return bool
	 */
	public static function onParserAfterParse( Parser &$parser = null, &$text = null, StripState $stripState = null ) {
		// this hook tries to access repo SiteLinkTable
		// it interferes with any test that parses something, like a page or a message
		if ( $parser === null || defined( 'MW_PHPUNIT_TEST' ) ) {
			return true;
		}

		$handler = self::newFromGlobalState();
		return $handler->doParserAfterParse( $parser );
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
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterParse
	 *
	 * @param Parser &$parser
	 *
	 * @return bool
	 */
	public function doParserAfterParse( Parser &$parser ) {
		$title = $parser->getTitle();

		if ( !$this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) ) {
			// shorten out
			return true;
		}

		wfProfileIn( __METHOD__ );

		// @todo split up the multiple responsibilities here and in lang link handler

		// only run this once, for the article content and not interface stuff
		//FIXME: this also runs for messages in EditPage::showEditTools! Ugh!
		if ( $parser->getOptions()->getInterfaceMessage() ) {
			wfProfileOut( __METHOD__ );
			return true;
		}

		$parserOutput = $parser->getOutput();

		try {
			if ( $this->langLinkHandler->useRepoLinks( $title, $parserOutput ) ) {
				// add links
				$this->langLinkHandler->addLinksFromRepository( $title, $parserOutput );

				if ( $this->alwaysSort ) {
					$interwikiLinks = $parserOutput->getLanguageLinks();
					$sortedLinks = $this->interwikiSorter->sortLinks( $interwikiLinks );
					$parserOutput->setLanguageLinks( $sortedLinks );
				}
			}

			$this->langLinkHandler->updateItemIdProperty( $title, $parserOutput );
			$this->langLinkHandler->updateOtherProjectsLinksData( $title, $parserOutput );
		} catch ( Exception $e ) {
			wfWarn( 'Failed to add repo links: ' . $e->getMessage() );
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

}
