<?php

namespace Wikibase;

use Sites;
use Wikibase\Repo\WikibaseRepo;

/**
 * Class for creating views for Wikibase\Item instances.
 * For the Wikibase\Item this basically is what the Parser is for WikitextContent.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner
 */
class ItemView extends EntityView {

	/**
	 * @see EntityView::getInnerHtml
	 */
	public function getInnerHtml( EntityRevision $entityRevision, $editable = true ) {
		$html = parent::getInnerHtml( $entityRevision, $editable );

		// add site-links to default entity stuff
		$html .= $this->getHtmlForSiteLinks( $entityRevision->getEntity(), $editable );

		return $html;
	}

	/**
	 * @see EntityView::getTocSections
	 */
	protected function getTocSections() {
		$array = parent::getTocSections();
		$array['claims'] = 'wikibase-statements';
		$groups = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'siteLinkGroups' );
		foreach( $groups as $group ) {
			$id = htmlspecialchars( 'sitelinks-' . $group, ENT_QUOTES );
			$array[$id] = 'wikibase-sitelinks-' . $group;
		}
		return $array;
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's site-links.
	 *
	 * @since 0.1
	 *
	 * @param Item $item the entity to render
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 *
	 * @return string
	 */
	public function getHtmlForSiteLinks( Item $item, $editable = true ) {
		$groups = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'siteLinkGroups' );
		$html = '';

		foreach ( $groups as $group ) {
			$html .= $this->getHtmlForSiteLinkGroup( $item, $group, $editable );
		}

		return $html;
	}

	/**
	 * Builds and returns the HTML representing a group of a WikibaseEntity's site-links.
	 *
	 * @since 0.4
	 *
	 * @param Item $item the entity to render
	 * @param string $group a site group ID
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtmlForSiteLinkGroup( Item $item, $group, $editable = true ) {
		$allSiteLinks = $item->getSimpleSiteLinks();

		$specialGroups = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( "specialSiteLinkGroups" );

		$siteLinks = array(); // site links of the currently handled site group

		foreach( $allSiteLinks as $siteLink ) {
			// FIXME: depracted method usage
			$site = Sites::singleton()->getSite( $siteLink->getSiteId() );

			if ( $site === null ) {
				continue;
			}

			$link = new SiteLink( $site, $siteLink->getPageName() );

			if ( $site->getGroup() === $group ) {
				$siteLinks[] = $link;
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

		// Batch load the sites we need info about during the building of the sitelink list.
		$sites = Sites::singleton()->getSiteGroup( $group );

		// Sort the sitelinks according to their global id
		$safetyCopy = $siteLinks; // keep a shallow copy;
		$sortOk = usort(
			$siteLinks,
			function( SiteLink $a, SiteLink $b ) {
				return strcmp( $a->getSite()->getGlobalId(), $b->getSite()->getGlobalId() );
			}
		);

		if ( !$sortOk ) {
			$siteLinks = $safetyCopy;
		}

		// Link to SpecialPage
		$editLink = $this->getEditUrl( 'SetSiteLink', $item, null );

		/* @var SiteLink $link */
		foreach( $siteLinks as $link ) {
			$alternatingClass = ( $i++ % 2 ) ? 'even' : 'uneven';

			$site = $link->getSite();

			if ( $site->getDomain() === '' ) {
				// the link is pointing to an unknown site.
				// XXX: hide it? make it red? strike it out?

				$tbody .= wfTemplate( 'wb-sitelink-unknown',
					$alternatingClass,
					htmlspecialchars( $link->getSite()->getGlobalId() ),
					htmlspecialchars( $link->getPage() ),
					$this->getHtmlForEditSection( $editLink, 'td' )
				);

			} else {
				$languageCode = $site->getLanguageCode();
				$escapedSiteId = htmlspecialchars( $site->getGlobalId() );
				// FIXME: this is a quickfix to allow a custom site-name for groups defined in $wgSpecialSiteLinkGroups instead of showing the language-name
				if ( in_array( $group, $specialGroups ) ) {
					$siteNameMsg = wfMessage( 'wikibase-sitelinks-sitename-' . $site->getGlobalId() );
					$siteName = $siteNameMsg->exists() ? $siteNameMsg->parse() : $site->getGlobalId();
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
					htmlspecialchars( $link->getUrl() ),
					htmlspecialchars( $link->getPage() ),
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

}
