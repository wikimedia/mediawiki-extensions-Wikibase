<?php

namespace Wikibase;
use Html, ParserOutput, Title, Language, OutputPage, Sites, MediaWikiSite;
use Wikibase\DataModel\SimpleSiteLink;
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
	public function getInnerHtml( EntityContent $entity, Language $lang = null, $editable = true ) {
		$html = parent::getInnerHtml( $entity, $lang, $editable );

		// add site-links to default entity stuff
		$html .= $this->getHtmlForSiteLinks( $entity, $lang, $editable );

		return $html;
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's site-links.
	 *
	 * @since 0.1
	 *
	 * @param EntityContent $itemContent the entity to render
	 * @param \Language|null $lang the language to use for rendering. if not given, the local context will be used.
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtmlForSiteLinks( EntityContent $item, Language $lang = null, $editable = true ) {
		$groups = Settings::get( "siteLinkGroups" );
		$html = '';

		foreach ( $groups as $group ) {
			$html .= $this->getHtmlForSiteLinkGroup( $item, $group, $lang, $editable );
		}

		return $html;
	}

	/**
	 * Builds and returns the HTML representing a group of a WikibaseEntity's site-links.
	 *
	 * @since 0.4
	 *
	 * @param EntityContent $itemContent the entity to render
	 * @param string $group a site group ID
	 * @param \Language|null $lang the language to use for rendering. if not given, the local context will be used.
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtmlForSiteLinkGroup( EntityContent $itemContent, $group, Language $lang = null, $editable = true ) {
		/**
		 * @var ItemContent $itemContent
		 */
		$allSiteLinks = $itemContent->getItem()->getSimpleSiteLinks();

		$siteLinks = array(); // site links of the currently handled site group

		foreach( $allSiteLinks as $siteLink ) {
			// FIXME: depracted method usage
			$site = \Sites::singleton()->getSite( $siteLink->getSiteId() );

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
			'wb-section-sitelink-heading',
			wfMessage( 'wikibase-sitelinks-' . $group )->parse(), // heading
			htmlspecialchars( 'sitelinks-' . $group, ENT_QUOTES ), // ID
			'wb-sitelink-heading'
			// TODO: support entity-id as prefix for element IDs.
		);

		if( !empty( $siteLinks ) ) {
			$thead = wfTemplate( 'wb-sitelinks-thead',
				wfMessage( 'wikibase-sitelinks-sitename-columnheading' )->parse(),
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
		$idFormatter = WikibaseRepo::getDefaultInstance()->getIdFormatter();
		$editLink = $this->getEditUrl( $idFormatter->format( $itemContent->getEntity()->getId() ), null, 'SetSiteLink' );

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
					$this->getHtmlForEditSection( $itemContent, $lang, $editLink, 'td' )
				);

			} else {
				$languageCode = $site->getLanguageCode();
				$escapedSiteId = htmlspecialchars( $site->getGlobalId() );

				// TODO: for non-JS, also set the dir attribute on the link cell;
				// but do not build language objects for each site since it causes too much load
				// and will fail when having too much site links
				$tbody .= wfTemplate( 'wb-sitelink',
					$languageCode,
					$alternatingClass,
					htmlspecialchars( Utils::fetchLanguageName( $languageCode ) ), // TODO: get an actual site name rather then just the language
					$escapedSiteId, // displayed site ID
					htmlspecialchars( $link->getUrl() ),
					htmlspecialchars( $link->getPage() ),
					$this->getHtmlForEditSection( $itemContent, $lang, $editLink . '/' . $escapedSiteId, 'td' ),
					$escapedSiteId // ID used in classes
				);
			}
		}

		// built table footer with button to add site-links, consider list could be complete!
		$isFull = count( $siteLinks ) >= count( $sites );

		$tfoot = wfTemplate( 'wb-sitelinks-tfoot',
			$isFull ? wfMessage( 'wikibase-sitelinksedittool-full' )->parse() : '',
			$this->getHtmlForEditSection( $itemContent, $lang, $editLink, 'td', 'add', !$isFull )
		);

		return $html . wfTemplate(
			'wb-sitelinks-table',
			$thead,
			$tbody,
			$tfoot,
			htmlspecialchars( $group )
		);
	}

}
