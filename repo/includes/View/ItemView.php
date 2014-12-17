<?php

namespace Wikibase\Repo\View;

use InvalidArgumentException;
use Language;
use Wikibase\DataModel\Entity\Item;
use Wikibase\EntityRevision;
use Wikibase\Template\TemplateFactory;

/**
 * Class for creating views for Item instances.
 * For the Item this basically is what the Parser is for WikitextContent.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner
 */
class ItemView extends EntityView {

	/**
	 * @var string[]
	 */
	private $siteLinkGroups;

	/**
	 * @var SiteLinksView
	 */
	private $siteLinksView;

	/**
	 * @see EntityView::__construct
	 *
	 * @param TemplateFactory $templateFactory
	 * @param FingerprintView $fingerprintView
	 * @param ClaimsView $claimsView
	 * @param Language $language
	 * @param SiteLinksView $siteLinksView
	 * @param string[] $siteLinkGroups
	 * @param bool $editable
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		FingerprintView $fingerprintView,
		ClaimsView $claimsView,
		Language $language,
		SiteLinksView $siteLinksView,
		array $siteLinkGroups,
		$editable = true
	) {
		parent::__construct( $templateFactory, $fingerprintView, $claimsView, $language, $editable );

		$this->siteLinkGroups = $siteLinkGroups;
		$this->siteLinksView = $siteLinksView;
	}

	/**
	 * @see EntityView::getMainHtml
	 */
	protected function getMainHtml( EntityRevision $entityRevision ) {
		$item = $entityRevision->getEntity();

		if ( !( $item instanceof Item ) ) {
			throw new InvalidArgumentException( '$entityRevision must contain an Item.' );
		}

		$html = parent::getMainHtml( $entityRevision );
		$html .= $this->claimsView->getHtml(
			$item->getStatements()->toArray()
		);

		return $html;
	}

	/**
	 * @see EntityView::getSideHtml
	 */
	protected function getSideHtml( EntityRevision $entityRevision ) {
		$item = $entityRevision->getEntity();
		return $this->getHtmlForSiteLinks( $item );
	}

	/**
	 * @see EntityView::getTocSections
	 */
	protected function getTocSections() {
		$array = parent::getTocSections();
		$array['claims'] = 'wikibase-statements';
		foreach ( $this->siteLinkGroups as $group ) {
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
	 *
	 * @return string
	 */
	protected function getHtmlForSiteLinks( Item $item ) {
		return $this->siteLinksView->getHtml(
			$item->getSiteLinks(),
			$item->getId(),
			$this->siteLinkGroups,
			$this->editable
		);
	}

}
