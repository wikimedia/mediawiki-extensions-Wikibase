<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use MediaWiki\Hook\SidebarBeforeOutputHook;
use MediaWiki\Hook\SkinTemplateGetLanguageLinkHook;
use MediaWiki\Output\Hook\OutputPageParserOutputHook;
use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Skin\Skin;
use MediaWiki\Title\Title;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\RepoLinker;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Store\EntityIdLookup;

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

	private EntityIdLookup $entityIdLookup;
	private EntityIdParser $entityIdParser;
	private NamespaceChecker $namespaceChecker;
	private LanguageLinkBadgeDisplay $badgeDisplay;
	private RepoLinker $repoLinker;

	public function __construct(
		EntityIdLookup $entityIdLookup,
		EntityIdParser $entityIdParser,
		LanguageLinkBadgeDisplay $badgeDisplay,
		NamespaceChecker $namespaceChecker,
		RepoLinker $repoLinker
	) {
		$this->entityIdLookup = $entityIdLookup;
		$this->entityIdParser = $entityIdParser;
		$this->namespaceChecker = $namespaceChecker;
		$this->badgeDisplay = $badgeDisplay;
		$this->repoLinker = $repoLinker;
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

		if ( $noExternalLangLinks ) {
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
	 * null value from {@link self::buildWikidataItemLink()} method and so it will
	 * skip attempting to add the link. Same thing repeats for the second case.
	 *
	 * @param Skin $skin
	 * @param array &$sidebar
	 */
	public function onSidebarBeforeOutput( $skin, &$sidebar ): void {
		// Add the 'In other projects' section
		$otherProjectsSidebar = $this->buildOtherProjectsSidebar( $skin );
		$sidebar['wikibase-otherprojects'] = $otherProjectsSidebar === null ? [] : $otherProjectsSidebar;

		// Add 'Wikidata item' to the toolbox
		$wikidataItemLink = $this->buildWikidataItemLink( $skin );

		if ( $wikidataItemLink === null ) {
			return;
		}

		if ( $skin->getSkinName() !== 'minerva' ) {
			$wikidataItemLink['class'] = 'wb-otherproject-link wb-otherproject-wikibase-dataitem';
			// This automatically appends the wikidata item link to the end of the Other Projects
			$sidebar['wikibase-otherprojects'][] = $wikidataItemLink;
		} else {
			$sidebar['TOOLBOX']['wikibase'] = $wikidataItemLink;
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

		if ( !$otherProjectsSidebar ) {
			return null;
		}

		return $otherProjectsSidebar;
	}

	/**
	 * Build 'Wikidata item' link for later addition to the toolbox section of the sidebar
	 *
	 * @param Skin $skin
	 *
	 * @return string[]|null Array of link elements or Null if link cannot be created.
	 */
	public function buildWikidataItemLink( Skin $skin ): ?array {
		$title = $skin->getTitle();
		$idString = $skin->getOutput()->getProperty( 'wikibase_item' );
		$entityId = null;

		if ( $idString !== null ) {
			$entityId = $this->entityIdParser->parse( $idString );
		} elseif ( $title &&
			$skin->getActionName() !== 'view' && $title->exists()
		) {
			// Try to load the item ID from Database, but only do so on non-article views,
			// (where the article's OutputPage isn't available to us).
			$entityId = $this->getEntityIdForTitle( $title );
		}

		if ( $entityId !== null ) {
			return [
				// Warning: This id is misleading; the 't' refers to the link's original place in the toolbox,
				// it now lives in the other projects section, but we must keep the 't' for compatibility with gadgets.
				'id' => 't-wikibase',
				'icon' => 'logoWikidata',
				'text' => $skin->msg( 'wikibase-dataitem' )->text(),
				'href' => $this->repoLinker->getEntityUrl( $entityId ),
			];
		}

		return null;
	}

	private function getEntityIdForTitle( Title $title ): ?EntityId {
		if ( !$this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) ) {
			return null;
		}

		return $this->entityIdLookup->getEntityIdForTitle( $title );
	}

}
