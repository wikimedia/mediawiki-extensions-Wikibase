<?php

namespace Wikibase\View;

use LanguageCode;
use Sanitizer;
use Site;
use SiteList;
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
	 * @var EntityIdFormatter
	 */
	private $entityIdFormatter;

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var SiteList
	 */
	private $sites;

	/**
	 * @var EditSectionGenerator
	 */
	private $sectionEditLinkGenerator;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @var NumberLocalizer
	 */
	private $numberLocalizer;

	/**
	 * @var string[]
	 */
	private $badgeItems;

	/**
	 * @var string[]
	 */
	private $specialSiteLinkGroups;

	/**
	 * @var LocalizedTextProvider
	 */
	private $textProvider;

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
		TemplateFactory $templateFactory,
		SiteList $sites,
		EditSectionGenerator $sectionEditLinkGenerator,
		EntityIdFormatter $entityIdFormatter,
		LanguageNameLookup $languageNameLookup,
		NumberLocalizer $numberLocalizer,
		array $badgeItems,
		array $specialSiteLinkGroups,
		LocalizedTextProvider $textProvider
	) {
		$this->templateFactory = $templateFactory;
		$this->sites = $sites;
		$this->sectionEditLinkGenerator = $sectionEditLinkGenerator;
		$this->entityIdFormatter = $entityIdFormatter;
		$this->languageNameLookup = $languageNameLookup;
		$this->numberLocalizer = $numberLocalizer;
		$this->badgeItems = $badgeItems;
		$this->specialSiteLinkGroups = $specialSiteLinkGroups;
		$this->textProvider = $textProvider;
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
	public function getHtml( array $siteLinks, ?ItemId $itemId, array $groups ) {
		$html = '';

		if ( empty( $groups ) ) {
			return $html;
		}

		foreach ( $groups as $group ) {
			$html .= $this->getHtmlForSiteLinkGroup( $siteLinks, $itemId, $group );
		}

		$html = $this->templateFactory->render( 'wikibase-sitelinkgrouplistview',
			$this->templateFactory->render( 'wikibase-listview', $html )
		);

		$sectionHeading = $this->getHtmlForSectionHeading( 'wikibase-sitelinks' );

		return $sectionHeading . $html;
	}

	/**
	 * Returns the HTML for the heading of the sitelinks section
	 *
	 * @param string $heading message key of the heading; also used as css class
	 *
	 * @return string HTML
	 */
	private function getHtmlForSectionHeading( $heading ) {
		$html = $this->templateFactory->render(
			'wb-section-heading',
			$this->textProvider->getEscaped( $heading ),
			'sitelinks', // ID - TODO: should not be added if output page is not the entity's page
			$heading
		);

		return $html;
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
	private function getHtmlForSiteLinkGroup( array $siteLinks, ?ItemId $itemId, $group ) {
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
	 *
	 * @param string $group
	 *
	 * @return SiteList
	 */
	private function getSitesForGroup( $group ) {
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
	 * @return array[]
	 */
	private function getSiteLinksForTable( SiteList $sites, array $itemSiteLinks ) {
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
		$safetyCopy = $siteLinksForTable; // keep a shallow copy
		$sortOk = usort(
			$siteLinksForTable,
			function( array $a, array $b ) {
				/** @var SiteLink[] $a */
				/** @var SiteLink[] $b */
				return strcmp( $a['siteLink']->getSiteId(), $b['siteLink']->getSiteId() );
			}
		);

		if ( !$sortOk ) {
			$siteLinksForTable = $safetyCopy;
		}

		return $siteLinksForTable;
	}

	/**
	 * @param array[] $siteLinksForTable
	 * @param bool $isSpecialGroup
	 *
	 * @return string HTML
	 */
	private function getHtmlForSiteLinks( array $siteLinksForTable, $isSpecialGroup ) {
		$html = '';

		foreach ( $siteLinksForTable as $siteLinkForTable ) {
			$html .= $this->getHtmlForSiteLink( $siteLinkForTable, $isSpecialGroup );
		}

		return $html;
	}

	/**
	 * @param array $siteLinkForTable
	 * @param bool $isSpecialGroup
	 *
	 * @return string HTML
	 */
	private function getHtmlForSiteLink( array $siteLinkForTable, $isSpecialGroup ) {
		/** @var Site $site */
		$site = $siteLinkForTable['site'];

		/** @var SiteLink $siteLink */
		$siteLink = $siteLinkForTable['siteLink'];

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
	private function getHtmlForPage( SiteLink $siteLink, Site $site ) {
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
	private function getHtmlForUnknownSiteLink( SiteLink $siteLink ) {
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
	private function getHtmlForBadges( array $badges ) {
		$html = '';

		foreach ( $badges as $badge ) {
			$serialization = $badge->getSerialization();
			$classes = Sanitizer::escapeClass( $serialization );
			if ( !empty( $this->badgeItems[$serialization] ) ) {
				$classes .= ' ' . Sanitizer::escapeClass( $this->badgeItems[$serialization] );
			}

			$html .= $this->templateFactory->render( 'wb-badge',
				$classes,
				$this->entityIdFormatter->formatEntityId( $badge ),
				$badge
			);
		}

		return $this->templateFactory->render( 'wikibase-badgeselector', $html );
	}

}
