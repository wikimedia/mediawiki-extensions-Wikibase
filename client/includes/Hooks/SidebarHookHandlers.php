<?php

namespace Wikibase\Client\Hooks;

use OutputPage;
use ParserOutput;
use Skin;
use StubUserLang;
use Title;
use Wikibase\Client\WikibaseClient;
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
	 * @var LanguageLinkBadgeDisplay
	 */
	private $badgeDisplay;

	/**
	 * @var bool
	 */
	private $otherProjectsLinksBeta;

	/**
	 * @var bool
	 */
	private $otherProjectsLinksDefault;

	public static function newFromGlobalState() {
		global $wgLang;
		StubUserLang::unstub( $wgLang );

		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();

		$namespaceChecker = $wikibaseClient->getNamespaceChecker();

		$entityLookup = $wikibaseClient->getStore()->getEntityLookup();
		$badgeClassNames = $settings->getSetting( 'badgeClassNames' );

		$badgeDisplay = new LanguageLinkBadgeDisplay(
			$entityLookup,
			is_array( $badgeClassNames ) ? $badgeClassNames : array(),
			$wgLang
		);

		return new SidebarHookHandlers(
			$namespaceChecker,
			$badgeDisplay,
			$wikibaseClient->getOtherProjectsSidebarGeneratorFactory(),
			$settings->getSetting( 'otherProjectsLinksBeta' ),
			$settings->getSetting( 'otherProjectsLinksByDefault' )
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
		LanguageLinkBadgeDisplay $badgeDisplay,
		OtherProjectsSidebarGeneratorFactory $otherProjectsSidebarGeneratorFactory,
		$otherProjectsLinksBeta,
		$otherProjectsLinksDefault
	) {
		$this->namespaceChecker = $namespaceChecker;
		$this->badgeDisplay = $badgeDisplay;
		$this->otherProjectsSidebarGeneratorFactory = $otherProjectsSidebarGeneratorFactory;
		$this->otherProjectsLinksBeta = $otherProjectsLinksBeta;
		$this->otherProjectsLinksDefault = $otherProjectsLinksDefault;
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
				$otherProjectsSidebarGenerator = $this->otherProjectsSidebarGeneratorFactory
					->getOtherProjectsSidebarGenerator();
				$otherProjectsSidebar = $otherProjectsSidebarGenerator->buildProjectLinkSidebar( $title );
			}

			if ( !empty( $otherProjectsSidebar ) ) {
				$sidebar['wikibase-otherprojects'] = $otherProjectsSidebar;
			}
		}

		return true;
	}

}
