<?php

namespace Wikibase\Repo\View;

use Message;
use SiteStore;
use Wikibase\DataModel\SiteLink;
use Wikibase\Item;
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
	 * @param Item $item the item to render
	 * @param string[] $groups An array of site group IDs
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 *
	 * @return string
	 */
	public function getHtml( Item $item, array $groups, $editable ) {
		$html = '';

		foreach ( $groups as $group ) {
			$html .= $this->getHtmlForSiteLinkGroup( $item, $group, $editable );
		}

		return $html;
	}

	/**
	 * Builds and returns the HTML representing a group of a WikibaseEntity's site-links.
	 *
	 * @param Item $item the entity to render
	 * @param string $group a site group ID
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	private function getHtmlForSiteLinkGroup( Item $item, $group, $editable = true ) {

		// FIXME: editable is completely unused

		$specialGroups = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( "specialSiteLinkGroups" );
		$isSpecialGroup = in_array( $group, $specialGroups );

		// @todo inject into constructor
		$sites = $this->siteStore->getSites()->getGroup( $group );
		$siteLinksForTable = $this->getSiteLinksForTable( $sites, $group, $item );

		$html = $thead = $tbody = $tfoot = '';

		$html .= wfTemplate(
			'wb-section-heading-sitelinks',
			wfMessage( 'wikibase-sitelinks-' . $group )->parse(), // heading
			htmlspecialchars( 'sitelinks-' . $group, ENT_QUOTES ) // ID
			// TODO: support entity-id as prefix for element IDs.
		);

		// Link to SpecialPage
		$editLink = $this->sectionEditLinkGenerator->getEditUrl( 'SetSiteLink', $item, null );

		if( !empty( $siteLinksForTable ) ) {
			$thead = $this->getTableHeadHtml( $isSpecialGroup );
			$tbody = $this->getTableBodyHtml( $siteLinksForTable, $editLink, $isSpecialGroup );
		}

		// Build table footer with button to add site-links, consider list could be complete!
		// The list is complete if it has a site link for every known site. Since
		// $siteLinksForTable only has an entry for links to existing sites, this
		// simple comparison works.
		$isFull = count( $siteLinksForTable ) >= count( $sites );
		$tfoot = $this->getTableFootHtml( $isFull, $editLink );

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
	 * @param string $group
	 * @param Item $item
	 */
	private function getSiteLinksForTable( $sites, $group, $item ) {
		$allSiteLinks = $item->getSiteLinks();
		$siteLinksForTable = array(); // site links of the currently handled site group

		foreach( $allSiteLinks as $siteLink ) {
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
	 * @param string $editLink
	 * @param bool $isSpecialGroup
	 */
	private function getTableBodyHtml( $siteLinksForTable, $editLink, $isSpecialGroup ) {
		$i = 0;
		$tbody = '';

		foreach( $siteLinksForTable as $siteLinkForTable ) {
			$alternatingClass = ( $i++ % 2 ) ? 'even' : 'uneven';
			$tbody .= $this->getHtmlForSiteLink( $siteLinkForTable, $editLink, $isSpecialGroup, $alternatingClass );
		}

		return $tbody;
	}

	/**
	 * @param bool $isFull
	 * @param string $editLink
	 */
	private function getTableFootHtml( $isFull, $editLink ) {
		$tfoot = wfTemplate( 'wb-sitelinks-tfoot',
			$isFull ? wfMessage( 'wikibase-sitelinksedittool-full' )->parse() : '',
			$this->getHtmlForEditSection( $editLink, 'td', 'add', !$isFull )
		);

		return $tfoot;
	}

	/**
	 * @param object $siteLinkForTable
	 * @param string $editLink
	 * @param bool $isSpecialGroup
	 * @param string $alternatingClass
	 */
	private function getHtmlForSiteLink( $siteLinkForTable, $editLink, $isSpecialGroup, $alternatingClass ) {
		/* @var Site $site */
		$site = $siteLinkForTable['site'];

		/* @var SiteLink $siteLink */
		$siteLink = $siteLinkForTable['siteLink'];

		if ( $site->getDomain() === '' ) {
			return $this->getHtmlForUnknownSiteLink( $siteLink, $editLink, $alternatingClass );
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
			$this->getHtmlForEditSection( $editLink . '/' . $escapedSiteId, 'td' ),
			$escapedSiteId // ID used in classes
		);
	}

	/**
	 * @param SiteLink $siteLink
	 * @param string $editLink
	 * @param string $alternatingClass
	 */
	private function getHtmlForUnknownSiteLink( $siteLink, $editLink, $alternatingClass ) {
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
			$this->getHtmlForEditSection( $editLink, 'td' )
		);
	}

	private function getHtmlForEditSection( $url, $tag = 'span', $action = 'edit', $enabled = true ) {
		$msg = new Message( 'wikibase-' . $action );

		return $this->sectionEditLinkGenerator->getHtmlForEditSection( $url, $msg, $tag, $enabled );
	}

}
