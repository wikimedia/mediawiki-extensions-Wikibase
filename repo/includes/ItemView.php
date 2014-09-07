<?php

namespace Wikibase;

use InvalidArgumentException;
use Language;
use Wikibase\Repo\View\ClaimsView;
use Wikibase\Repo\View\FingerprintView;
use Wikibase\Repo\View\SiteLinksView;
use Wikibase\Repo\View\TocGenerator;

/**
 * Class for creating views for Item instances.
 * For the Item this basically is what the Parser is for WikitextContent.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ItemView extends EntityView {

	/**
	 * @var FingerprintView
	 */
	private $fingerprintView;

	/**
	 * @var ClaimsView
	 */
	private $claimsView;

	/**
	 * @var SiteLinksView
	 */
	private $siteLinksView;

	/**
	 * @var string[]
	 */
	private $siteLinkGroups;

	public function __construct(
		FingerprintView $fingerprintView,
		ClaimsView $claimsView,
		SiteLinksView $siteLinksView,
		array $siteLinkGroups,
		Language $language
	) {
		parent::__construct( $language );

		$this->fingerprintView = $fingerprintView;
		$this->claimsView = $claimsView;
		$this->siteLinksView = $siteLinksView;
		$this->siteLinkGroups = $siteLinkGroups;
	}

	/**
	 * @see EntityView::getInnerHtml
	 */
	protected function getInnerHtml( EntityRevision $entityRevision, $editable = true ) {
		$item = $entityRevision->getEntity();

		if ( !( $item instanceof Item ) ) {
			throw new InvalidArgumentException( '$entityRevision must contain an Item.' );
		}

		$html = '';
		$html .= $this->fingerprintView->getHtml( $item->getFingerprint(), $item->getId(), $editable );
		$html .= $this->getHtmlForToc();
		$html .= $this->getHtmlForTermBox( $entityRevision );
		$html .= $this->claimsView->getHtml( $item->getClaims(), 'wikibase-statements' );
		$html .= $this->siteLinksView->getHtml( $item->getSiteLinkList(), $item->getId(), $this->siteLinkGroups, $editable );

		return $html;
	}

	private function getHtmlForToc() {
		$tocSections = array(
			// Placeholder for the TOC entry for the term box (which may or may not be used for a given user).
			// EntityViewPlaceholderExpander must know about the 'termbox-toc' name.
			// This is a hack because the marker does not exist as a system message it will be added as is.
			'termbox' => $this->textInjector->newMarker( 'termbox-toc' ),
			'claims' => 'wikibase-statements'
		);

		foreach ( $this->siteLinkGroups as $group ) {
			$id = htmlspecialchars( 'sitelinks-' . $group, ENT_QUOTES );
			$tocSections[$id] = 'wikibase-sitelinks-' . $group;
		}

		$tocGenerator = new TocGenerator();

		return $tocGenerator->getHtmlForToc( $tocSections );
	}

	private function getHtmlForTermBox( EntityRevision $entityRevision ) {
		if ( $entityRevision->getEntity()->getId() ) {
			// Placeholder for a termbox for the present item.
			// EntityViewPlaceholderExpander must know about the parameters used here.
			return $this->textInjector->newMarker(
				'termbox',
				$entityRevision->getEntity()->getId()->getSerialization(),
				$entityRevision->getRevision()
			);
		}

		return '';
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
	 *
	private function getHtmlForSiteLinks( Item $item, $editable = true ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$groups = $wikibaseRepo->getSettings()->getSetting( 'siteLinkGroups' );

		// FIXME: Inject this
		$siteLinksView = new SiteLinksView(
			$wikibaseRepo->getSiteStore()->getSites(),
			new SectionEditLinkGenerator(),
			$wikibaseRepo->getEntityLookup(),
			$this->language->getCode()
		);

		$itemId = $item->getId();

		return $siteLinksView->getHtml( $item->getSiteLinks(), $itemId, $groups, $editable );
	}
	*/

}
