<?php

namespace Wikibase\Client\Hooks;

use Exception;
use OutputPage;
use Parser;
use ParserOutput;
use Skin;
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

	/**
	 * @var bool
	 */
	private $otherProjectsLinksBeta;

	/**
	 * @var bool
	 */
	private $otherProjectsLinksDefault;

	/**
	 * @var OtherProjectsSidebarGenerator
	 */
	private $otherProjectsSidebarGenerator;

	public static function newFromGlobalState() {
		global $wgLang;
		StubUserLang::unstub( $wgLang );

		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$namespaceChecker = $wikibaseClient->getNamespaceChecker();
		$langLinkHandler = $wikibaseClient->getLangLinkHandler();
		$entityLookup = $wikibaseClient->getStore()->getEntityLookup();
		$badgeDisplay = $wikibaseClient->getLanguageLinkBadgeDisplay( $wgLang );

		$settings = $wikibaseClient->getSettings();

		$interwikiSorter = new InterwikiSorter(
			$settings->getSetting( 'sort' ),
			$settings->getSetting( 'interwikiSortOrders' ),
			$settings->getSetting( 'sortPrepend' )
		);

		$otherProjectsSidebarGenerator = $wikibaseClient->getOtherProjectsSidebarGenerator();

		return new SidebarHookHandlers(
			$namespaceChecker,
			$langLinkHandler,
			$badgeDisplay,
			$interwikiSorter,
			$otherProjectsSidebarGenerator,
			$settings
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
	 * @param OutputPage $output
	 *
	 * @return bool
	 */
	public static function onSkinTemplateGetLanguageLink( &$languageLink, Title $languageLinkTitle, Title $title, OutputPage $output = null ) {
		$handler = self::newFromGlobalState();
		return $handler->doSkinTemplateGetLanguageLink( $languageLink, $languageLinkTitle, $title, $output );
	}

	/**
	 * Adds the "other projects" section to the sidebar, if enabled project wide or
	 * the user has the beta featured enabled.
	 *
	 * @param Skin $skin
	 * @param array &$sidebar
	 *
	 * @return bool
	 */
	public static function onSidebarBeforeOutput( Skin $skin, array &$sidebar ) {
		$handler = self::newFromGlobalState();
		return $handler->doSidebarBeforeOutput( $skin, $sidebar );
	}

	public function __construct(
		NamespaceChecker $namespaceChecker,
		LangLinkHandler $langLinkHandler,
		LanguageLinkBadgeDisplay $badgeDisplay,
		InterwikiSorter $sorter,
		OtherProjectsSidebarGenerator $otherProjectsSidebarGenerator,
		SettingsArray $settings
	) {

		$this->namespaceChecker = $namespaceChecker;
		$this->langLinkHandler = $langLinkHandler;
		$this->badgeDisplay = $badgeDisplay;
		$this->interwikiSorter = $sorter;
		$this->otherProjectsSidebarGenerator = $otherProjectsSidebarGenerator;

		$this->alwaysSort = $settings->getSetting( 'alwaysSort' );
		$this->otherProjectsLinksBeta = $settings->getSetting( 'otherProjectsLinksBeta' );
		$this->otherProjectsLinksDefault = $settings->getSetting( 'otherProjectsLinksByDefault' );
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

		$noExternalLangLinks = NoLangLinkHandler::getNoExternalLangLinks( $parserOutput );

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

		$badges = $parserOutput->getExtensionData( 'wikibase_badges' );

		if ( $badges !== null ) {
			$out->setProperty( 'wikibase_badges', $badges );
		}

		return true;
	}

	/**
	 * Add badges to the language links.
	 *
	 * @param array &$languageLink
	 * @param Title $languageLinkTitle
	 * @param Title $title
	 * @param OutputPage|null $output
	 *
	 * @return bool
	 */
	public function doSkinTemplateGetLanguageLink( &$languageLink, Title $languageLinkTitle, Title $title, OutputPage $output = null ) {
		if ( !$output ) {
			// This would happen for versions of core that do not have change Ic479e2fa5cc applied.
			wfWarn( __METHOD__ . ': SkinTemplateGetLanguageLink hook called without OutputPage object!' );
			return true;
		}

		wfProfileIn( __METHOD__ );

		$this->badgeDisplay->applyBadges( $languageLink, $languageLinkTitle, $output );

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Adds the "other projects" section to the sidebar, if enabled project wide or
	 * the user has the beta featured enabled.
	 *
	 * @param Skin $skin
	 * @param array &$sidebar
	 *
	 * @return bool
	 */
	public function doSidebarBeforeOutput( Skin $skin, array &$sidebar ) {
		$outputPage = $skin->getContext()->getOutput();
		$title = $outputPage->getTitle();

		if ( !$this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) ) {
			return true;
		}

		$betaFeatureEnabled = class_exists( '\BetaFeatures' ) &&
			$this->otherProjectsLinksBeta &&
			\BetaFeatures::isFeatureEnabled( $skin->getUser(), 'wikibase-otherprojects' );

		if ( $this->otherProjectsLinksDefault || $betaFeatureEnabled ) {
			$otherProjectsSidebar = $outputPage->getProperty( 'wikibase-otherprojects-sidebar' );

			// in case of stuff in cache without the other projects
			if ( $otherProjectsSidebar === null ) {
				$otherProjectsSidebar = $this->otherProjectsSidebarGenerator->buildProjectLinkSidebar( $title );
			}

			if ( !empty( $otherProjectsSidebar ) ) {
				$sidebar['wikibase-otherprojects'] = $otherProjectsSidebar;
			}
		}

		return true;
	}

}
