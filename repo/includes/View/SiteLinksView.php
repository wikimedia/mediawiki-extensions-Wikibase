<?php

namespace Wikibase\Repo\View;

use Message;
use SiteList;
use SiteStore;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Utils;

class SiteLinksView {

	private $siteStore;
	private $sectionEditLinkGenerator;

	public function __construct( SiteStore $siteStore, SectionEditLinkGenerator $sectionEditLinkGenerator ) {
		$this->siteStore = $siteStore;
		$this->sectionEditLinkGenerator = $sectionEditLinkGenerator;
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's site-links.
	 *
	 * @since 0.5
	 *
	 * @param SiteLink[] $siteLinks the site links to render
	 * @param EntityId $entityId The id of the entity
	 * @param string[] $groups An array of site group IDs
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 *
	 * @return string
	 */
	public function getHtml( array $siteLinks, EntityId $entityId, array $groups, $editable ) {
		// FIXME: editable is completely unused
		
		$html = '';

		foreach ( $groups as $group ) {
			$html .= $this->getHtmlForSiteLinkGroup( $siteLinks, $entityId, $group );
		}

		return $html;
	}

	/**
	 * Builds and returns the HTML representing a group of a WikibaseEntity's site-links.
	 *
	 * @param SiteLink[] $siteLinks the site links to render
	 * @param EntityId $entityId The id of the entity
	 * @param string $group a site group ID
	 *
	 * @return string
	 */
	private function getHtmlForSiteLinkGroup( array $siteLinks, EntityId $entityId, $group ) {
		$specialGroups = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( "specialSiteLinkGroups" );
		$isSpecialGroup = in_array( $group, $specialGroups );

		// @todo inject into constructor
		$sites = $this->siteStore->getSites()->getGroup( $group );
		$siteLinksForTable = $this->getSiteLinksForTable( $sites, $siteLinks );

		$html = $thead = $tbody = $tfoot = '';

		$html .= wfTemplate(
			'wb-section-heading-sitelinks',
			wfMessage( 'wikibase-sitelinks-' . $group )->parse(), // heading
			htmlspecialchars( 'sitelinks-' . $group, ENT_QUOTES ) // ID
			// TODO: support entity-id as prefix for element IDs.
		);

		if( !empty( $siteLinksForTable ) ) {
			$thead = $this->getTableHeadHtml( $isSpecialGroup );
			$tbody = $this->getTableBodyHtml( $siteLinksForTable, $entityId, $isSpecialGroup );
		}

		// Build table footer with button to add site-links, consider list could be complete!
		// The list is complete if it has a site link for every known site. Since
		// $siteLinksForTable only has an entry for links to existing sites, this
		// simple comparison works.
		$isFull = count( $siteLinksForTable ) >= count( $sites );
		$tfoot = $this->getTableFootHtml( $entityId, $isFull );

		$groupName = $isSpecialGroup ? 'special' : $group;

		return $html . wfTemplate(
			'wb-sitelinks-table',
			$thead,
			$tbody,
			$tfoot,
			htmlspecialchars( $groupName )
		);
	}

	/**
	 * @param SiteList $sites
	 * @param SiteLink[] $itemSiteLinks
	 *
	 * @return array[]
	 */
	private function getSiteLinksForTable( SiteList $sites, array $itemSiteLinks ) {
		$siteLinksForTable = array(); // site links of the currently handled site group

		foreach( $itemSiteLinks as $siteLink ) {
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
		// FIXME: quickfix to allow a custom site-name / handling for groups defined in $wgSpecialSiteLinkGroups
		$siteNameMessageKey = 'wikibase-sitelinks-sitename-columnheading';
		if ( $isSpecialGroup ) {
			$siteNameMessageKey .= '-special';
		}

		$thead = wfTemplate( 'wb-sitelinks-thead',
			wfMessage( $siteNameMessageKey )->parse(),
			wfMessage( 'wikibase-sitelinks-siteid-columnheading' )->parse(),
			wfMessage( 'wikibase-sitelinks-link-columnheading' )->parse()
		);

		return $thead;
	}

	/**
	 * @param object[] $siteLinksForTable
	 * @param EntityId $entityId The id of the entity
	 * @param bool $isSpecialGroup
	 *
	 * @return string
	 */
	private function getTableBodyHtml( $siteLinksForTable, $entityId, $isSpecialGroup ) {
		$i = 0;
		$tbody = '';

		foreach( $siteLinksForTable as $siteLinkForTable ) {
			$alternatingClass = ( $i++ % 2 ) ? 'even' : 'uneven';
			$tbody .= $this->getHtmlForSiteLink( $siteLinkForTable, $entityId, $isSpecialGroup, $alternatingClass );
		}

		return $tbody;
	}

	/**
	 * @param EntityId $entityId The id of the entity
	 * @param bool $isFull
	 *
	 * @return string
	 */
	private function getTableFootHtml( $entityId, $isFull ) {
		$tfoot = wfTemplate( 'wb-sitelinks-tfoot',
			$isFull ? wfMessage( 'wikibase-sitelinksedittool-full' )->parse() : '',
			'<td>' . $this->getHtmlForEditSection( $entityId, '', 'add', !$isFull ) . '</td>'
		);

		return $tfoot;
	}

	/**
	 * @param object $siteLinkForTable
	 * @param EntityId $entityId The id of the entity
	 * @param bool $isSpecialGroup
	 * @param string $alternatingClass
	 *
	 * @return string
	 */
	private function getHtmlForSiteLink( $siteLinkForTable, $entityId, $isSpecialGroup, $alternatingClass ) {
		/* @var \Site $site */
		$site = $siteLinkForTable['site'];

		/* @var SiteLink $siteLink */
		$siteLink = $siteLinkForTable['siteLink'];

		if ( $site->getDomain() === '' ) {
			return $this->getHtmlForUnknownSiteLink( $siteLink, $entityId, $alternatingClass );
		}

		$languageCode = $site->getLanguageCode();
		$pageName = $siteLink->getPageName();
		$siteId = $siteLink->getSiteId();
		$escapedPageName = htmlspecialchars( $pageName );
		$escapedSiteId = htmlspecialchars( $siteId );

		// FIXME: this is a quickfix to allow a custom site-name for groups defined in $wgSpecialSiteLinkGroups instead of showing the language-name
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
		return wfTemplate( 'wb-sitelink',
			$languageCode,
			$alternatingClass,
			$siteName,
			$escapedSiteId, // displayed site ID
			htmlspecialchars( $site->getPageUrl( $pageName ) ),
			$escapedPageName,
			'<td>' . $this->getHtmlForEditSection( $entityId, $escapedSiteId ) . '</td>',
			$escapedSiteId // ID used in classes
		);
	}

	/**
	 * @param SiteLink $siteLink
	 * @param EntityId $entityId The id of the entity
	 * @param string $alternatingClass
	 *
	 * @return string
	 */
	private function getHtmlForUnknownSiteLink( $siteLink, $entityId, $alternatingClass ) {
		// the link is pointing to an unknown site.
		// XXX: hide it? make it red? strike it out?

		$pageName = $siteLink->getPageName();
		$siteId = $siteLink->getSiteId();
		$escapedPageName = htmlspecialchars( $pageName );
		$escapedSiteId = htmlspecialchars( $siteId );

		return wfTemplate( 'wb-sitelink-unknown',
			$alternatingClass,
			$escapedSiteId,
			$escapedPageName,
			'<td>' . $this->getHtmlForEditSection( $entityId ) . '</td>'
		);
	}

	private function getHtmlForEditSection( EntityId $entityId, $subPage = '', $action = 'edit', $enabled = true ) {
		$msg = new Message( 'wikibase-' . $action );
		$specialPage = 'SetSiteLink';
		$specialPageParams = array(
			$entityId->getPrefixedId()
		);
		if( $subPage !== '' ) {
			$specialPageParams[] = $subPage;
		}

		return $this->sectionEditLinkGenerator->getHtmlForEditSection(
			$specialPage,
			$specialPageParams,
			$msg,
			$enabled
		);
	}

}
