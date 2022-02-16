<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use MediaWiki\Hook\OutputPageParserOutputHook;
use MediaWiki\Hook\SidebarBeforeOutputHook;
use MediaWiki\Hook\SkinTemplateGetLanguageLinkHook;
use OutputPage;
use ParserOutput;
use Skin;
use Title;
use Wikibase\Client\ClientHooks;
use Wikibase\Client\NamespaceChecker;

/**
 * Handler for ParserOutput-related hooks.
 *
 * @license GPL-2.0-or-later
 */
class SidebarHookHandler implements
	OutputPageParserOutputHook,
	SkinTemplateGetLanguageLinkHook,
	SidebarBeforeOutputHook
{

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @var LanguageLinkBadgeDisplay
	 */
	private $badgeDisplay;

	public function __construct(
		LanguageLinkBadgeDisplay $badgeDisplay,
		NamespaceChecker $namespaceChecker
	) {
		$this->namespaceChecker = $namespaceChecker;
		$this->badgeDisplay = $badgeDisplay;
	}

	/**
	 * Add output page property if repo links are suppressed, and property for item id
	 *
	 * @param OutputPage $outputPage
	 * @param ParserOutput $parserOutput
	 */
	public function onOutputPageParserOutput( $outputPage, $parserOutput ): void {
		$title = $outputPage->getTitle();
		if ( !$title || !$this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) ) {
			// shorten out
			return;
		}

		$noExternalLangLinks = NoLangLinkHandler::getNoExternalLangLinks( $parserOutput );

		if ( !empty( $noExternalLangLinks ) ) {
			$outputPage->setProperty( 'noexternallanglinks', $noExternalLangLinks );
		}

		$itemId = $parserOutput->getPageProperty( 'wikibase_item' );

		if ( $itemId !== null ) {
			$outputPage->setProperty( 'wikibase_item', $itemId );
		}

		$otherProjects = $parserOutput->getExtensionData( 'wikibase-otherprojects-sidebar' );

		if ( $otherProjects !== null ) {
			$outputPage->setProperty( 'wikibase-otherprojects-sidebar', $otherProjects );
		}

		$badges = $parserOutput->getExtensionData( 'wikibase_badges' );

		if ( $badges !== null ) {
			$outputPage->setProperty( 'wikibase_badges', $badges );
		}
	}

	/**
	 * Add badges to the language links.
	 *
	 * @param array &$languageLink
	 * @param Title $languageLinkTitle
	 * @param Title $title
	 * @param OutputPage|null $output
	 */
	public function onSkinTemplateGetLanguageLink(
		&$languageLink,
		$languageLinkTitle,
		$title,
		$output
	): void {
		$this->badgeDisplay->applyBadges( $languageLink, $languageLinkTitle, $output );
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
	 * If current page cannot have 'Wikidata item' link, this callback will receive
	 * null value from ClientHooks::buildWikidataItemLink() method and so it will
	 * skip attempting to add the link. Same thing repeats for the second case.
	 *
	 * @param Skin $skin
	 * @param array &$sidebar
	 */
	public function onSidebarBeforeOutput( $skin, &$sidebar ): void {
		// Add 'Wikidata item' to the toolbox
		$wikidataItemLink = ClientHooks::buildWikidataItemLink( $skin );

		if ( $wikidataItemLink !== null ) {
			$sidebar['TOOLBOX']['wikibase'] = $wikidataItemLink;
		}

		// Add the 'In other projects' section
		$otherProjectsSidebar = $this->buildOtherProjectsSidebar( $skin );

		if ( $otherProjectsSidebar !== null ) {
			$sidebar['wikibase-otherprojects'] = $otherProjectsSidebar;
		}
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
