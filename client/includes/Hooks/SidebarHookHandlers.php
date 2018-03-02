<?php

namespace Wikibase\Client\Hooks;

use OutputPage;
use ParserOutput;
use Skin;
use Title;
use Wikibase\Client\WikibaseClient;
use Wikibase\Client\NamespaceChecker;

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
	 * Adds the "other projects" section to the sidebar, if enabled project wide or
	 * the user has the beta featured enabled.
	 *
	 * @param Skin $skin
	 * @param array &$sidebar
	 *
	 * @return bool
	 */
	public static function onSidebarBeforeOutput( Skin $skin, array &$sidebar ) {
		$handler = self::getInstance();
		return $handler->doSidebarBeforeOutput( $skin, $sidebar );
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

		if ( !$title || !$outputPage->getProperty( 'wikibase_item' ) ) {
			return true;
		}

		$otherProjectsSidebar = $outputPage->getProperty( 'wikibase-otherprojects-sidebar' );

		// in case of stuff in cache without the other projects
		if ( $otherProjectsSidebar === null ) {
			$otherProjectsSidebarGenerator = $this->otherProjectsSidebarGeneratorFactory
				->getOtherProjectsSidebarGenerator();
			$otherProjectsSidebar = $otherProjectsSidebarGenerator->buildProjectLinkSidebar( $title );
		}

		if ( !empty( $otherProjectsSidebar ) ) {
			$sidebar['wikibase-otherprojects'] = $otherProjectsSidebar;
		}

		return true;
	}

}
