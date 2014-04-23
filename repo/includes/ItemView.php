<?php

namespace Wikibase;

use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\SiteLinksView;

/**
 * Class for creating views for Wikibase\Item instances.
 * For the Wikibase\Item this basically is what the Parser is for WikitextContent.
 *
 * @since 0.1
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
	 * Returns the HTML for the heading of the claims section
	 *
	 * @since 0.5
	 *
	 * @param Entity $entity
	 * @param bool $editable
	 *
	 * @return string
	 */
	protected function getHtmlForClaimsSectionHeading( Entity $entity, $editable = true ) {
		$html = wfTemplate(
			'wb-section-heading',
			wfMessage( 'wikibase-statements' ),
			'claims' // ID - TODO: should not be added if output page is not the entity's page
		);

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

		// FIXME: Inject this
		$siteLinksView = new SiteLinksView(
			WikibaseRepo::getDefaultInstance()->getSiteStore(),
			$this->sectionEditLinkGenerator
		);

		return $siteLinksView->getHtml( $item, $groups, $editable );
	}

}
