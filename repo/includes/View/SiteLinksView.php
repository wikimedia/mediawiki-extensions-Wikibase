<?php

namespace Wikibase\Repo\View;

use InvalidArgumentException;
use Message;
use Sanitizer;
use Site;
use SiteList;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Utils;

/**
 * Creates views for lists of site links.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SiteLinksView {

	/**
	 * @var SiteList
	 */
	private $sites;

	/**
	 * @var SectionEditLinkGenerator
	 */
	private $sectionEditLinkGenerator;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @var string[]
	 */
	private $specialSiteLinkGroups;

	/**
	 * @var array
	 */
	private $badgeItems;

	public function __construct( SiteList $sites, SectionEditLinkGenerator $sectionEditLinkGenerator,
			EntityLookup $entityLookup, $languageCode ) {
		$this->sites = $sites;
		$this->sectionEditLinkGenerator = $sectionEditLinkGenerator;
		$this->entityLookup = $entityLookup;
		$this->languageCode = $languageCode;

		// @todo inject option/objects instead of using the singleton
		$repo = WikibaseRepo::getDefaultInstance();

		$settings = $repo->getSettings();
		$this->specialSiteLinkGroups = $settings->getSetting( 'specialSiteLinkGroups' );
		$this->badgeItems = $settings->getSetting( 'badgeItems' );
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's site-links.
	 *
	 * @since 0.5
	 *
	 * @param SiteLinkList $siteLinks the site links to render
	 * @param ItemId|null $itemId The id of the item or might be null, if a new item.
	 * @param string[] $groups An array of site group IDs
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function getHtml( SiteLinkList $siteLinks, ItemId $itemId = null, array $groups, $editable ) {
		$html = '';

		if ( count( $groups ) === 0 ) {
			return $html;
		}

		foreach ( $groups as $group ) {
			$html .= $this->getHtmlForSiteLinkGroup( $siteLinks, $itemId, $group, $editable );
		}

		return wfTemplate( 'wikibase-sitelinkgrouplistview',
			wfTemplate( 'wb-listview', $html )
		);
	}

	/**
	 * Builds and returns the HTML representing a group of a WikibaseEntity's site-links.
	 *
	 * @param SiteLinkList $siteLinks the site links to render
	 * @param ItemId|null $itemId The id of the item
	 * @param string $group a site group ID
	 * @param bool $editable
	 *
	 * @return string
	 */
	private function getHtmlForSiteLinkGroup( SiteLinkList $siteLinks, $itemId, $group, $editable ) {
		$isSpecialGroup = $group === 'special';

		$sites = $this->getSitesForGroup( $group );
		$siteLinksForTable = $this->getSiteLinksForTable( $sites, $siteLinks );

		$html = $thead = $tbody = $tfoot = '';

		if ( !empty( $siteLinksForTable ) ) {
			$thead = $this->getTableHeadHtml( $isSpecialGroup );
			$tbody = $this->getTableBodyHtml(
				$siteLinksForTable,
				$itemId,
				$isSpecialGroup,
				$editable
			);
		}

		// Build table footer with button to add site-links, consider list could be complete!
		// The list is complete if it has a site link for every known site. Since
		// $siteLinksForTable only has an entry for links to existing sites, this
		// simple comparison works.
		$isFull = count( $siteLinksForTable ) >= count( $sites );
		$tfoot = $this->getTableFootHtml( $itemId, $isFull, $editable );

		return $html . wfTemplate( 'wikibase-sitelinkgroupview',
			// TODO: support entity-id as prefix for element IDs.
			htmlspecialchars( 'sitelinks-' . $group, ENT_QUOTES ),
			wfMessage( 'wikibase-sitelinks-' . $group )->parse(),
			'', // counter
			wfTemplate( 'wikibase-sitelinklistview',
				$thead,
				$tbody,
				$tfoot
			),
			htmlspecialchars( $group ),
			'<div>' . $this->getHtmlForEditSection( $itemId, '', 'edit', $editable ) . '</div>'
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
			$groups = array( $group );
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
	 * @param SiteLinkList $siteLinks
	 *
	 * @return array[]
	 */
	private function getSiteLinksForTable( SiteList $sites, SiteLinkList $siteLinks ) {
		$siteLinksForTable = array(); // site links of the currently handled site group

		foreach ( $siteLinks as $siteLink ) {
			if ( !$sites->hasSite( $siteLink->getSiteId() ) ) {
				// FIXME: Maybe show it instead
				continue;
			}

			$site = $sites->getSite( $siteLink->getSiteId() );

			$siteLinksForTable[] = array(
				'siteLink' => $siteLink,
				'site' => $site
			);
		}

		// Sort the sitelinks according to their global id
		$safetyCopy = $siteLinksForTable; // keep a shallow copy
		$sortOk = usort(
			$siteLinksForTable,
			function( $a, $b ) {
				return strcmp( $a['siteLink']->getSiteId(), $b['siteLink']->getSiteId() );
			}
		);

		if ( !$sortOk ) {
			$siteLinksForTable = $safetyCopy;
		}

		return $siteLinksForTable;
	}

	/**
	 * @param bool $isSpecialGroup
	 *
	 * @return string
	 */
	private function getTableHeadHtml( $isSpecialGroup ) {
		// FIXME: quickfix to allow a custom site-name / handling for the site groups which are
		// special according to the specialSiteLinkGroups setting
		$siteNameMessageKey = 'wikibase-sitelinks-sitename-columnheading';
		if ( $isSpecialGroup ) {
			$siteNameMessageKey .= '-special';
		}

		$thead = wfTemplate( 'wikibase-sitelinklistview-thead',
			wfMessage( $siteNameMessageKey )->parse(),
			wfMessage( 'wikibase-sitelinks-siteid-columnheading' )->parse(),
			wfMessage( 'wikibase-sitelinks-link-columnheading' )->parse()
		);

		return $thead;
	}

	/**
	 * @param object[] $siteLinksForTable
	 * @param ItemId|null $itemId
	 * @param bool $isSpecialGroup
	 *
	 * @return string
	 */
	private function getTableBodyHtml( $siteLinksForTable, $itemId, $isSpecialGroup ) {
		$tbody = '';

		foreach ( $siteLinksForTable as $siteLinkForTable ) {
			$tbody .= $this->getHtmlForSiteLink(
				$siteLinkForTable,
				$itemId,
				$isSpecialGroup
			);
		}

		return $tbody;
	}

	/**
	 * @param ItemId|null $itemId for the Item
	 * @param bool $isFull
	 * @param bool $editable
	 *
	 * @return string
	 */
	private function getTableFootHtml( $itemId, $isFull, $editable ) {
		$editSection = $this->getHtmlForEditSection( $itemId, '', 'add', !$isFull && $editable );

		$tfoot = wfTemplate( 'wikibase-sitelinklistview-tfoot',
			$isFull ? wfMessage( 'wikibase-sitelinksedittool-full' )->parse() : '',
			$editSection
		);

		return $tfoot;
	}

	/**
	 * @param object $siteLinkForTable
	 * @param ItemId|null $itemId The id of the item
	 * @param bool $isSpecialGroup
	 *
	 * @return string
	 */
	private function getHtmlForSiteLink( $siteLinkForTable, $itemId, $isSpecialGroup ) {
		/** @var Site $site */
		$site = $siteLinkForTable['site'];

		/** @var SiteLink $siteLink */
		$siteLink = $siteLinkForTable['siteLink'];

		if ( $site->getDomain() === '' ) {
			return $this->getHtmlForUnknownSiteLink( $siteLink, $itemId );
		}

		$languageCode = $site->getLanguageCode();
		$siteId = $siteLink->getSiteId();

		// FIXME: this is a quickfix to allow a custom site-name for the site groups which are
		// special according to the specialSiteLinkGroups setting
		if ( $isSpecialGroup ) {
			// FIXME: not escaped?
			$siteNameMsg = wfMessage( 'wikibase-sitelinks-sitename-' . $siteId );
			$siteName = $siteNameMsg->exists() ? $siteNameMsg->parse() : $siteId;
		} else {
			// TODO: get an actual site name rather then just the language
			$siteName = htmlspecialchars( Utils::fetchLanguageName( $languageCode ) );
		}

		// TODO: for non-JS, also set the dir attribute on the link cell;
		// but do not build language objects for each site since it causes too much load
		// and will fail when having too much site links
		return wfTemplate( 'wikibase-sitelinkview',
			htmlspecialchars( $siteId ), // ID used in classes
			$languageCode,
			'auto',
			$siteName,
			htmlspecialchars( $siteId ), // displayed site ID
			$this->getHtmlForPage( $siteLink, $site )
		);
	}

	/**
	 * @param SiteLink $siteLink
	 * @param Site $site
	 *
	 * @return string
	 */
	private function getHtmlForPage( $siteLink, $site ) {
		$pageName = $siteLink->getPageName();

		return wfTemplate( 'wikibase-sitelinkview-pagename',
			htmlspecialchars( $site->getPageUrl( $pageName ) ),
			htmlspecialchars( $pageName ),
			$this->getHtmlForBadges( $siteLink ),
			$site->getLanguageCode(),
			'auto'
		);
	}

	/**
	 * @param SiteLink $siteLink
	 * @param ItemId|null $itemId The id of the item
	 *
	 * @return string
	 */
	private function getHtmlForUnknownSiteLink( $siteLink, $itemId ) {
		$siteId = $siteLink->getSiteId();
		$pageName = $siteLink->getPageName();

		// FIXME: No need for separate template; Use 'wikibase-sitelinkview' template.
		return wfTemplate( 'wikibase-sitelinkview-unknown',
			htmlspecialchars( $siteId ),
			htmlspecialchars( $pageName ),
			$this->getHtmlForEditSection( $itemId, $siteId )
		);
	}

	/**
	 * @param ItemId|null $itemId
	 * @param string $subPage defaults to ''
	 * @param string $action defaults to 'edit'
	 * @param bool $enabled defaults to true
	 *
	 * @return string
	 */
	private function getHtmlForEditSection( $itemId, $subPage = '', $action = 'edit', $enabled = true ) {
		$specialPageUrlParams = array();

		if ( $itemId !== null ) {
			$specialPageUrlParams[] = $itemId->getSerialization();

			if ( $subPage !== '' ) {
				$specialPageUrlParams[] = $subPage;
			}
		}

		return $this->sectionEditLinkGenerator->getHtmlForEditSection(
			'SetSiteLink',
			$specialPageUrlParams,
			new Message( 'wikibase-' . $action ),
			$enabled
		);
	}

	private function getHtmlForBadges( SiteLink $siteLink ) {
		$html = '';

		/** @var ItemId $badge */
		foreach ( $siteLink->getBadges() as $badge ) {
			$serialization = $badge->getSerialization();
			$classes = Sanitizer::escapeClass( $serialization );
			if ( !empty( $this->badgeItems[$serialization] ) ) {
				$classes .= ' ' . Sanitizer::escapeClass( $this->badgeItems[$serialization] );
			}

			$html .= wfTemplate( 'wb-badge',
				$classes,
				$this->getTitleForBadge( $badge ),
				$badge
			);
		}

		return wfTemplate( 'wikibase-badgeselector', $html );
	}

	/**
	 * Returns the title for the given badge id.
	 * @todo use TermLookup when we have one
	 *
	 * @param EntityId $badgeId
	 *
	 * @return string
	 */
	private function getTitleForBadge( EntityId $badgeId ) {
		$entity = $this->entityLookup->getEntity( $badgeId );
		if ( $entity === null ) {
			return $badgeId->getSerialization();
		}

		$labels = $entity->getFingerprint()->getLabels();
		if ( $labels->hasTermForLanguage( $this->languageCode ) ) {
			return $labels->getByLanguage( $this->languageCode )->getText();
		} else {
			return $badgeId->getSerialization();
		}
	}

}
