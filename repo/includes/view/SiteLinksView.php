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
	 * @todo: code in this function needs to be split up
	 *
	 * @param Item $item the entity to render
	 * @param string $group a site group ID
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	private function getHtmlForSiteLinkGroup( Item $item, $group, $editable = true ) {
		// @todo inject into constructor
		$sites = $this->siteStore->getSites();
		$specialGroups = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( "specialSiteLinkGroups" );

		$allSiteLinks = $item->getSiteLinks();
		$siteLinks = array(); // site links of the currently handled site group

		foreach( $allSiteLinks as $siteLink ) {
			$site = $sites->getSite( $siteLink->getSiteId() );

			if ( $site === null ) {
				continue;
			}

			if ( $site->getGroup() === $group ) {
				$siteLinks[] = $siteLink;
			}
		}

		$html = $thead = $tbody = $tfoot = '';

		$html .= wfTemplate(
			'wb-section-heading-sitelinks',
			wfMessage( 'wikibase-sitelinks-' . $group )->parse(), // heading
			htmlspecialchars( 'sitelinks-' . $group, ENT_QUOTES ) // ID
			// TODO: support entity-id as prefix for element IDs.
		);

		// FIXME: quickfix to allow a custom site-name / handling for groups defined in $wgSpecialSiteLinkGroups
		$siteNameMessageKey = 'wikibase-sitelinks-sitename-columnheading';
		if ( in_array( $group, $specialGroups ) ) {
			$siteNameMessageKey .= '-special';
		}

		if( !empty( $siteLinks ) ) {
			$thead = wfTemplate( 'wb-sitelinks-thead',
				wfMessage( $siteNameMessageKey )->parse(),
				wfMessage( 'wikibase-sitelinks-siteid-columnheading' )->parse(),
				wfMessage( 'wikibase-sitelinks-link-columnheading' )->parse()
			);
		}

		$i = 0;

		// Sort the sitelinks according to their global id
		$safetyCopy = $siteLinks; // keep a shallow copy;
		$sortOk = usort(
			$siteLinks,
			function( SimpleSiteLink $a, SimpleSiteLink $b ) {
				return strcmp( $a->getSiteId(), $b->getSiteId() );
			}
		);

		if ( !$sortOk ) {
			$siteLinks = $safetyCopy;
		}

		// Link to SpecialPage
		$editLink = $this->sectionEditLinkGenerator->getEditUrl( 'SetSiteLink', $item, null );

		/* @var SimpleSiteLink $link */
		foreach( $siteLinks as $siteLink ) {
			$alternatingClass = ( $i++ % 2 ) ? 'even' : 'uneven';

			$siteId = $siteLink->getSiteId();
			$pageName = $siteLink->getPageName();

			$site = $sites->hasSite( $siteId ) ? $sites->getSite( $siteId ) : null;

			if ( !$site || $site->getDomain() === '' ) {
				// the link is pointing to an unknown site.
				// XXX: hide it? make it red? strike it out?

				$tbody .= wfTemplate( 'wb-sitelink-unknown',
					$alternatingClass,
					htmlspecialchars( $siteId ),
					htmlspecialchars( $siteLink->getPageName() ),
					$this->getHtmlForEditSection( $editLink, 'td' )
				);

			} else {
				$languageCode = $site->getLanguageCode();
				$escapedSiteId = htmlspecialchars( $siteId );
				// FIXME: this is a quickfix to allow a custom site-name for groups defined in $wgSpecialSiteLinkGroups instead of showing the language-name
				if ( in_array( $group, $specialGroups ) ) {
					$siteNameMsg = wfMessage( 'wikibase-sitelinks-sitename-' . $siteId );
					$siteName = $siteNameMsg->exists() ? $siteNameMsg->parse() : $siteId;
				} else {
					// TODO: get an actual site name rather then just the language
					$siteName = htmlspecialchars( Utils::fetchLanguageName( $languageCode ) );
				}

				// TODO: for non-JS, also set the dir attribute on the link cell;
				// but do not build language objects for each site since it causes too much load
				// and will fail when having too much site links
				$tbody .= wfTemplate( 'wb-sitelink',
					$languageCode,
					$alternatingClass,
					$siteName,
					$escapedSiteId, // displayed site ID
					htmlspecialchars( $site->getPageUrl( $pageName ) ),
					htmlspecialchars( $pageName ),
					$this->getHtmlForEditSection( $editLink . '/' . $escapedSiteId, 'td' ),
					$escapedSiteId // ID used in classes
				);
			}
		}

		// built table footer with button to add site-links, consider list could be complete!
		$isFull = count( $siteLinks ) >= count( $sites );

		$tfoot = wfTemplate( 'wb-sitelinks-tfoot',
			$isFull ? wfMessage( 'wikibase-sitelinksedittool-full' )->parse() : '',
			$this->getHtmlForEditSection( $editLink, 'td', 'add', !$isFull )
		);

		$groupName = in_array( $group, $specialGroups ) ? 'special' : $group;

		return $html . wfTemplate(
			'wb-sitelinks-table',
			$thead,
			$tbody,
			$tfoot,
			htmlspecialchars( $groupName )
		);
	}

	private function getHtmlForEditSection( $url, $tag = 'span', $action = 'edit', $enabled = true ) {
		$msg = new Message( 'wikibase-' . $action );

		return $this->sectionEditLinkGenerator->getHtmlForEditSection( $url, $msg, $tag, $enabled );
	}

}
