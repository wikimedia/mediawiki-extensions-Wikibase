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

/**
 * ParserOutput related hook handlers.
 *
 * This class has a static interface for use with MediaWiki's hook mechanism; the static
 * handler functions will create a new instance of SidebarHookHandlers and then call the
 * corresponding member function on that.
 *
 * @since 0.5.
 *
 * @license GPL 2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class SidebarHookHandlers {

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @var LangLinkHandler
	 */
	private $langLinkHandler;

	/**
	 * @var LanguageLinkBadgeDisplay
	 */
	private $badgeDisplay;

	/**
	 * @var InterwikiSorter
	 */
	private $interwikiSorter;

	/**
	 * @var bool
	 */
	private $alwaysSort;

	private static function newFromGlobalState() {
		global $wgLang;
		StubUserLang::unstub( $wgLang );

		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();

		$namespaceChecker = $wikibaseClient->getNamespaceChecker();
		$langLinkHandler = $wikibaseClient->getLangLinkHandler();

		$clientSiteLinkLookup = $wikibaseClient->getClientSiteLinkLookup();
		$entityLookup = $wikibaseClient->getStore()->getEntityLookup();
		$siteStore = $wikibaseClient->getSiteStore();
		$badgeClassNames = $settings->getSetting( 'badgeClassNames' );

		$badgeDisplay = new LanguageLinkBadgeDisplay(
			$clientSiteLinkLookup,
			$entityLookup,
			$siteStore,
			is_array( $badgeClassNames ) ? $badgeClassNames : array(),
			$wgLang
		);

		$interwikiSorter = new InterwikiSorter(
			$settings->getSetting( 'sort' ),
			$settings->getSetting( 'interwikiSortOrders' ),
			$settings->getSetting( 'sortPrepend' )
		);

		return new SidebarHookHandlers(
			$namespaceChecker,
			$langLinkHandler,
			$badgeDisplay,
			$interwikiSorter,
			$settings->getSetting( 'alwaysSort' )
		);
	}

	/**
	 * Static handler for the ParserAfterParse hook.
	 *
	 * @param Parser &$parser
	 * @param string &$text
	 * @param StripState $stripState
	 *
	 * @return bool
	 */
	public static function onParserAfterParse( Parser &$parser, &$text, StripState $stripState ) {
		// this hook tries to access repo SiteLinkTable
		// it interferes with any test that parses something, like a page or a message
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			return true;
		}

		$handler = self::newFromGlobalState();
		return $handler->doParserAfterParse( $parser, $text, $stripState );
	}

	/**
	 * Static handler for the OutputPageParserOutput hook.
	 *
	 * @param OutputPage &$out
	 * @param ParserOutput $parserOutput
	 *
	 * @return bool
	 */
	public static function onOutputPageParserOutput( OutputPage &$out, ParserOutput $parserOutput ) {
		$handler = self::newFromGlobalState();
		return $handler->doOutputPageParserOutput( $out, $parserOutput );
	}

	/**
	 * Static handler for the SkinTemplateGetLanguageLink hook.
	 *
	 * @param &$languageLink
	 * @param Title $languageLinkTitle
	 * @param Title $title
	 *
	 * @return bool
	 */
	public static function onSkinTemplateGetLanguageLink( &$languageLink, Title $languageLinkTitle, Title $title ) {
		$handler = self::newFromGlobalState();
		return $handler->doSkinTemplateGetLanguageLink( $languageLink, $languageLinkTitle, $title );
	}

	public function __construct(
		NamespaceChecker $namespaceChecker,
		LangLinkHandler $langLinkHandler,
		LanguageLinkBadgeDisplay $badgeDisplay,
		InterwikiSorter $sorter,
		$alwaysSort
	) {

		$this->namespaceChecker = $namespaceChecker;
		$this->langLinkHandler = $langLinkHandler;
		$this->badgeDisplay = $badgeDisplay;
		$this->interwikiSorter = $sorter;
		$this->alwaysSort = $alwaysSort;
	}

	/**
	 * Hook runs after internal parsing
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterParse
	 *
	 * @param Parser &$parser
	 * @param string &$text
	 * @param StripState $stripState
	 *
	 * @return bool
	 */
	public function doParserAfterParse( Parser &$parser, &$text, StripState $stripState ) {
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
		$useRepoLinks = $this->langLinkHandler->useRepoLinks( $title, $parserOutput );

		try {
			if ( $useRepoLinks ) {
				// add links
				$this->langLinkHandler->addLinksFromRepository( $title, $parserOutput );
			}

			$this->langLinkHandler->updateItemIdProperty( $title, $parserOutput );
			$this->langLinkHandler->updateOtherProjectsLinksData( $title, $parserOutput );
		} catch ( Exception $e ) {
			wfWarn( 'Failed to add repo links: ' . $e->getMessage() );
		}

		if ( $useRepoLinks || $this->alwaysSort ) {
			$interwikiLinks = $parserOutput->getLanguageLinks();
			$sortedLinks = $this->interwikiSorter->sortLinks( $interwikiLinks );
			$parserOutput->setLanguageLinks( $sortedLinks );
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Add output page property if repo links are suppressed, and property for item id
	 *
	 * @param OutputPage &$out
	 * @param ParserOutput $parserOutput
	 *
	 * @return bool
	 */
	public function doOutputPageParserOutput( OutputPage &$out, ParserOutput $parserOutput ) {
		if ( !$this->namespaceChecker->isWikibaseEnabled( $out->getTitle()->getNamespace() ) ) {
			// shorten out
			return true;
		}

		$noExternalLangLinks = $this->langLinkHandler->getNoExternalLangLinks( $parserOutput );

		if ( !empty( $noExternalLangLinks ) ) {
			$out->setProperty( 'noexternallanglinks', $noExternalLangLinks );
		}

		$itemId = $parserOutput->getProperty( 'wikibase_item' );

		if ( $itemId !== false ) {
			$out->setProperty( 'wikibase_item', $itemId );
		}

		$otherProjects = $parserOutput->getExtensionData( 'wikibase-otherprojects-sidebar' );

		if ( $otherProjects !== null ) {
			$out->setProperty( 'wikibase-otherprojects-sidebar', $otherProjects );
		}

		return true;
	}

	/**
	 * Add badges to the language links.
	 *
	 * @since 0.5
	 *
	 * @param array &$languageLink
	 * @param Title $languageLinkTitle
	 * @param Title $title
	 *
	 * @return bool
	 */
	public function doSkinTemplateGetLanguageLink( &$languageLink, Title $languageLinkTitle, Title $title ) {
		wfProfileIn( __METHOD__ );

		$this->badgeDisplay->assignBadges( $title, $languageLinkTitle, $languageLink );

		wfProfileOut( __METHOD__ );
		return true;
	}

}
