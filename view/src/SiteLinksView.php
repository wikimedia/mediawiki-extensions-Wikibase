<?php

namespace Wikibase\View;

use MediaWiki\Language\LanguageCode;
use MediaWiki\Parser\Sanitizer;
use MediaWiki\Site\Site;
use MediaWiki\Site\SiteList;
use ValueFormatters\NumberLocalizer;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\View\Template\TemplateFactory;

/**
 * Creates views for lists of site links.
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SiteLinksView {

	/**
	 * @param TemplateFactory $templateFactory
	 * @param SiteList $sites
	 * @param EditSectionGenerator $sectionEditLinkGenerator
	 * @param EntityIdFormatter $entityIdFormatter A plaintext producing EntityIdFormatter
	 * @param LanguageNameLookup $languageNameLookup
	 * @param NumberLocalizer $numberLocalizer
	 * @param string[] $badgeItems
	 * @param string[] $specialSiteLinkGroups
	 * @param LocalizedTextProvider $textProvider
	 */
	public function __construct(
		private readonly TemplateFactory $templateFactory,
		private readonly SiteList $sites,
		private readonly EditSectionGenerator $sectionEditLinkGenerator,
		private readonly EntityIdFormatter $entityIdFormatter,
		private readonly LanguageNameLookup $languageNameLookup,
		private readonly NumberLocalizer $numberLocalizer,
		private readonly array $badgeItems,
		private readonly array $specialSiteLinkGroups,
		private readonly LocalizedTextProvider $textProvider,
	) {
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's site-links.
	 *
	 * @param SiteLink[] $siteLinks the site links to render
	 * @param ItemId|null $itemId The id of the item or might be null, if a new item.
	 * @param string[] $groups An array of site group IDs
	 *
	 * @return string HTML
	 */
	public function getHtml( array $siteLinks, ?ItemId $itemId, array $groups ): string {
		$html = '';

		if ( !$groups ) {
			return $html;
		}

		foreach ( $groups as $group ) {
			$html .= $this->getHtmlForSiteLinkGroup( $siteLinks, $itemId, $group );
		}

		return $this->templateFactory->render( 'wb-section-heading',
				$this->textProvider->getEscaped( 'wikibase-sitelinks' ),
				'sitelinks', // ID - TODO: should not be added if output page is not the entity's page
				'wikibase-sitelinks'
			) .
			$this->templateFactory->render( 'wikibase-sitelinkgrouplistview',
				$this->templateFactory->render( 'wikibase-listview', $html )
			);
	}

	/**
	 * Builds and returns the HTML representing a group of a WikibaseEntity's site-links.
	 *
	 * @param SiteLink[] $siteLinks the site links to render
	 * @param ItemId|null $itemId The id of the item
	 * @param string $group a site group ID
	 *
	 * @return string HTML
	 */
	private function getHtmlForSiteLinkGroup( array $siteLinks, ?ItemId $itemId, $group ): string {
		$siteLinksForTable = $this->getSiteLinksForTable(
			$this->getSitesForGroup( $group ),
			$siteLinks
		);

		$count = count( $siteLinksForTable );

		return $this->templateFactory->render(
			'wikibase-sitelinkgroupview',
			// TODO: support entity-id as prefix for element IDs.
			htmlspecialchars( 'sitelinks-' . $group, ENT_QUOTES ),
			$this->textProvider->getEscaped( 'wikibase-sitelinks-' . $group ),
			$this->textProvider->getEscaped( 'parentheses', [
				$this->textProvider->get(
					'wikibase-sitelinks-counter',
					[
						$this->numberLocalizer->localizeNumber( $count ),
					]
				),
			] ),
			$this->templateFactory->render(
				'wikibase-sitelinklistview',
				$this->getHtmlForSiteLinks( $siteLinksForTable, $group === 'special' )
			),
			htmlspecialchars( $group ),
			$this->sectionEditLinkGenerator->getSiteLinksEditSection( $itemId ),
			$count > 1 ? ' mw-collapsible' : ''
		);
	}

	/**
	 * Get all sites for a given site group, with special handling for the
	 * "special" site group.
	 */
	private function getSitesForGroup( string $group ): SiteList {
		$siteList = new SiteList();

		if ( $group === 'special' ) {
			$groups = $this->specialSiteLinkGroups;
		} else {
			$groups = [ $group ];
		}

		foreach ( $groups as $group ) {
			$sites = $this->sites->getGroup( $group );
			foreach ( $sites as $site ) {
				$siteList->setSite( $site );
			}
		}

		return $siteList;
	}

	/**
	 * @param SiteList $sites
	 * @param SiteLink[] $itemSiteLinks
	 *
	 * @return array<array{siteLink: SiteLink, site: Site}>
	 */
	private function getSiteLinksForTable( SiteList $sites, array $itemSiteLinks ): array {
		/** @var array<array{siteLink: SiteLink, site: Site}> $siteLinksForTable */
		$siteLinksForTable = []; // site links of the currently handled site group

		foreach ( $itemSiteLinks as $siteLink ) {
			if ( !$sites->hasSite( $siteLink->getSiteId() ) ) {
				// FIXME: Maybe show it instead
				continue;
			}

			$site = $sites->getSite( $siteLink->getSiteId() );

			$siteLinksForTable[] = [
				'siteLink' => $siteLink,
				'site' => $site,
			];
		}

		// Sort the sitelinks according to their global id
		usort(
			$siteLinksForTable,
			function( array $a, array $b ) {
				/** @var array{siteLink:SiteLink} $a */
				/** @var array{siteLink:SiteLink} $b */
				return $a['siteLink']->getSiteId() <=> $b['siteLink']->getSiteId();
			}
		);

		return $siteLinksForTable;
	}

	/**
	 * @param array<array{siteLink: SiteLink, site: Site}> $siteLinksForTable
	 * @param bool $isSpecialGroup
	 *
	 * @return string HTML
	 */
	private function getHtmlForSiteLinks( array $siteLinksForTable, bool $isSpecialGroup ): string {
		$html = '';

		foreach ( $siteLinksForTable as $siteLinkForTable ) {
			$html .= $this->getHtmlForSiteLink( $siteLinkForTable, $isSpecialGroup );
		}

		return $html;
	}

	/**
	 * @param array{siteLink: SiteLink, site: Site} $siteLinkForTable
	 * @param bool $isSpecialGroup
	 *
	 * @return string HTML
	 */
	private function getHtmlForSiteLink( array $siteLinkForTable, bool $isSpecialGroup ): string {
		[ 'siteLink' => $siteLink, 'site' => $site ] = $siteLinkForTable;

		if ( $site->getDomain() === '' ) {
			return $this->getHtmlForUnknownSiteLink( $siteLink );
		}

		$languageCode = $site->getLanguageCode();
		$siteId = $siteLink->getSiteId();

		// FIXME: this is a quickfix to allow a custom site-name for the site groups which are
		// special according to the specialSiteLinkGroups setting
		if ( $isSpecialGroup ) {
			$siteNameMsg = 'wikibase-sitelinks-sitename-' . $siteId;
			$siteName = $this->textProvider->has( $siteNameMsg ) ? $this->textProvider->get( $siteNameMsg ) : $siteId;
		} else {
			// TODO: get an actual site name rather then just the language
			$siteName = $this->languageNameLookup->getName( $languageCode );
		}

		return $this->templateFactory->render( 'wikibase-sitelinkview',
			htmlspecialchars( $siteId ), // ID used in classes
			htmlspecialchars( $siteId ), // displayed site ID
			htmlspecialchars( $siteName ),
			$this->getHtmlForPage( $siteLink, $site )
		);
	}

	/**
	 * @param SiteLink $siteLink
	 * @param Site $site
	 *
	 * @return string HTML
	 */
	private function getHtmlForPage( SiteLink $siteLink, Site $site ): string {
		$pageName = $siteLink->getPageName();

		return $this->templateFactory->render( 'wikibase-sitelinkview-pagename',
			htmlspecialchars( $site->getPageUrl( $pageName ) ),
			htmlspecialchars( $pageName ),
			$this->getHtmlForBadges( $siteLink->getBadges() ),
			htmlspecialchars( LanguageCode::bcp47( $site->getLanguageCode() ) ),
			'auto'
		);
	}

	/**
	 * @param SiteLink $siteLink
	 *
	 * @return string HTML
	 */
	private function getHtmlForUnknownSiteLink( SiteLink $siteLink ): string {
		// FIXME: No need for separate template; Use 'wikibase-sitelinkview' template.
		return $this->templateFactory->render( 'wikibase-sitelinkview-unknown',
			htmlspecialchars( $siteLink->getSiteId() ),
			htmlspecialchars( $siteLink->getPageName() )
		);
	}

	/**
	 * @param ItemId[] $badges
	 *
	 * @return string HTML
	 */
	private function getHtmlForBadges( array $badges ): string {
		$html = '';

		foreach ( $badges as $badge ) {
			$serialization = $badge->getSerialization();
			$classes = Sanitizer::escapeClass( $serialization );
			if ( !empty( $this->badgeItems[$serialization] ) ) {
				$classes .= ' ' . Sanitizer::escapeClass( $this->badgeItems[$serialization] );
			}

			$html .= $this->templateFactory->render( 'wb-badge',
				$classes,
				htmlspecialchars( $this->entityIdFormatter->formatEntityId( $badge ) ),
				$badge->getSerialization()
			);
		}

		return $this->templateFactory->render( 'wikibase-badgeselector', $html );
	}

}
