<?php

namespace Wikibase\View;

use Message;
use SiteStore;
use Wikibase\DataModel\SimpleSiteLink;
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
		$sites = $this->siteStore->getSites();
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
			$thead = $this->getTableHead( $isSpecialGroup );
			$tbody = $this->getTableBody( $siteLinksForTable, $editLink, $isSpecialGroup );
		}

		$tfoot = $this->getTableFoot( $sites, $siteLinksForTable, $editLink );

		$groupName = $isSpecialGroup ? 'special' : $group;

		return $html . wfTemplate(
			'wb-sitelinks-table',
			$thead,
			$tbody,
			$tfoot,
			htmlspecialchars( $groupName )
		);
	}

	private function getSiteLinksForTable( $sites, $group, $item ) {
		$allSiteLinks = $item->getSiteLinks();
		$siteLinksForTable = array(); // site links of the currently handled site group

		foreach( $allSiteLinks as $siteLink ) {
			$site = $sites->getSite( $siteLink->getSiteId() );

			if ( $site === null ) {
				// FIXME: Maybe show it instead
				continue;
			}

			if ( $site->getGroup() === $group ) {
				$siteLinksForTable[] = array(
					'siteLink' => $siteLink,
					'site' => $site
				);
			}
		}

		return $siteLinksForTable;
	}

	private function getTableHead( $isSpecialGroup ) {
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

	private function getTableBody( $siteLinksForTable, $editLink, $isSpecialGroup ) {
		// Sort the sitelinks according to their global id
		$safetyCopy = $siteLinksForTable; // keep a shallow copy
		$sortOk = usort(
			$siteLinksForTable,
			function( $a, $b ) {
				return strcmp( $a->siteLink->getSiteId(), $b->siteLink->getSiteId() );
			}
		);

		if ( !$sortOk ) {
			$siteLinksForTable = $safetyCopy;
		}

		$i = 0;
		$tbody = '';

		/* @var SimpleSiteLink $siteLink */
		foreach( $siteLinksForTable as $siteLinkForTable ) {
			$alternatingClass = ( $i++ % 2 ) ? 'even' : 'uneven';
			$tbody .= $this->getHtmlForSiteLink( $siteLinkForTable, $editLink, $isSpecialGroup, $alternatingClass );
		}

		return $tbody;
	}

	private function getTableFoot( $sites, $siteLinks, $editLink ) {
		// built table footer with button to add site-links, consider list could be complete!
		// FIXME: This is broken. We would have to check that there is a siteLink for
		// every site instead of checking that there are at least as many siteLinks as sites.
		$isFull = count( $siteLinks ) >= count( $sites );

		$tfoot = wfTemplate( 'wb-sitelinks-tfoot',
			$isFull ? wfMessage( 'wikibase-sitelinksedittool-full' )->parse() : '',
			$this->getHtmlForEditSection( $editLink, 'td', 'add', !$isFull )
		);

		return $tfoot;
	}

	private function getHtmlForSiteLink( $siteLinkForTable, $editLink, $isSpecialGroup, $alternatingClass ) {
		$site = $siteLinkForTable['site'];

		$siteLink = $siteLinkForTable['siteLink'];
		$siteId = $siteLink->getSiteId();
		$pageName = $siteLink->getPageName();
		$escapedPageName = htmlspecialchars( $pageName );
		$escapedSiteId = htmlspecialchars( $siteId );

		if ( $site->getDomain() === '' ) {
			// the link is pointing to an unknown site.
			// XXX: hide it? make it red? strike it out?

			return wfTemplate( 'wb-sitelink-unknown',
				$alternatingClass,
				$escapedSiteId,
				$escapedPageName,
				$this->getHtmlForEditSection( $editLink, 'td' )
			);

		} else {
			$languageCode = $site->getLanguageCode();
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
	}

	private function getHtmlForEditSection( $url, $tag = 'span', $action = 'edit', $enabled = true ) {
		$msg = new Message( 'wikibase-' . $action );

		return $this->sectionEditLinkGenerator->getHtmlForEditSection( $url, $msg, $tag, $enabled );
	}

}
