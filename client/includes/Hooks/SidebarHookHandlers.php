<?php

namespace Wikibase\Client\Hooks;

use OutputPage;
use ParserOutput;
use Skin;
use Title;
use Wikibase\Client\ClientHooks;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\WikibaseClient;

/**
 * ParserOutput related hook handlers.
 *
 * This class has a static interface for use with MediaWiki's hook mechanism; the static
 * handler functions will create a new instance of SidebarHookHandlers and then call the
 * corresponding member function on that.
 *
 * @license GPL-2.0-or-later
 */
class SidebarHookHandlers {

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @var LanguageLinkBadgeDisplay
	 */
	private $badgeDisplay;

	/**
	 * @var OtherProjectsSidebarGeneratorFactory
	 */
	private $otherProjectsSidebarGeneratorFactory;

	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * @return self
	 */
	private static function getInstance() {
		if ( self::$instance === null ) {
			self::$instance = self::newFromGlobalState();
		}

		return self::$instance;
	}

	public static function newFromGlobalState() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$settings = $wikibaseClient->getSettings();

		return new self(
			$wikibaseClient->getNamespaceChecker(),
			$wikibaseClient->getLanguageLinkBadgeDisplay(),
			$wikibaseClient->getOtherProjectsSidebarGeneratorFactory()
		);
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
		$handler = self::getInstance();
		return $handler->doOutputPageParserOutput( $out, $parserOutput );
	}

	/**
	 * Static handler for the SkinTemplateGetLanguageLink hook.
	 *
	 * @param array &$languageLink
	 * @param Title $languageLinkTitle
	 * @param Title $title
	 * @param OutputPage|null $output
	 *
	 * @return bool
	 */
	public static function onSkinTemplateGetLanguageLink(
		array &$languageLink,
		Title $languageLinkTitle,
		Title $title,
		OutputPage $output = null
	) {
		$handler = self::getInstance();
		return $handler->doSkinTemplateGetLanguageLink(
			$languageLink,
			$languageLinkTitle,
			$title,
			$output
		);
	}

	/**
	 * SidebarBeforeOutput hook handler
	 *
	 * This handler adds too items to the sidebar section.
	 * First it adds the 'Wikidata items' to the 'toolbox' section of the sidebar.
	 * Second it adds the 'In other projects' item which lives in its own section.
	 *
	 * The items generation logic are handled separately for each. This callback
	 * is only concerned with adding them to the &$sidebar array (if they exist).
	 *
	 * If currrent page cannot have 'Wikidata item' link, this callback will receive
	 * null value from ClientHooks::buildWikidataItemLink() method and so it will
	 * skip attempting to add the link. Same thing repeats for the second case.
	 *
	 * @param Skin $skin
	 * @param array &$sidebar
	 */
	public static function onSidebarBeforeOutput( Skin $skin, array &$sidebar ) {
		// Add 'Wikidata item' to the toolbox
		$wikidataItemLink = ClientHooks::buildWikidataItemLink( $skin );

		if ( $wikidataItemLink !== null ) {
			$sidebar['TOOLBOX']['wikibase'] = $wikidataItemLink;
		}

		// Add the 'In other projects' section
		$handler = self::getInstance();
		$otherProjectsSidebar = $handler->buildOtherProjectsSidebar( $skin );

		if ( $otherProjectsSidebar !== null ) {
			$sidebar['wikibase-otherprojects'] = $otherProjectsSidebar;
		}
	}

	public function __construct(
		NamespaceChecker $namespaceChecker,
		LanguageLinkBadgeDisplay $badgeDisplay,
		OtherProjectsSidebarGeneratorFactory $otherProjectsSidebarGeneratorFactory
	) {
		$this->namespaceChecker = $namespaceChecker;
		$this->badgeDisplay = $badgeDisplay;
		$this->otherProjectsSidebarGeneratorFactory = $otherProjectsSidebarGeneratorFactory;
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
		$title = $out->getTitle();
		if ( !$title || !$this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) ) {
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
	public function doSkinTemplateGetLanguageLink(
		array &$languageLink,
		Title $languageLinkTitle,
		Title $title,
		OutputPage $output = null
	) {
		if ( !$output ) {
			// This would happen for versions of core that do not have change Ic479e2fa5cc applied.
			wfWarn( __METHOD__ . ': SkinTemplateGetLanguageLink hook called without OutputPage object!' );
			return true;
		}

		$this->badgeDisplay->applyBadges( $languageLink, $languageLinkTitle, $output );

		return true;
	}

	/**
	 * Build 'In other projects' section of the sidebar, if enabled project wide or
	 * the user has the beta featured enabled.
	 *
	 * @param Skin $skin
	 *
	 * @return null|array[] Array of 'In other projects' contents or null if there are none
	 */
	public function buildOtherProjectsSidebar( Skin $skin ): ?array {
		$outputPage = $skin->getContext()->getOutput();

		$otherProjectsSidebar = $outputPage->getProperty( 'wikibase-otherprojects-sidebar' );

		if ( empty( $otherProjectsSidebar ) ) {
			return null;
		}

		return $otherProjectsSidebar;
	}

}
