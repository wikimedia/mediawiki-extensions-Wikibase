<?php

namespace Wikibase\View;

use InvalidArgumentException;
use Language;
use Wikibase\DataModel\Entity\Item;
use Wikibase\EntityRevision;
use Wikibase\View\Template\TemplateFactory;

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
	 * @var StatementGroupListView
	 */
	private $statementGroupListView;

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
	 * @param EntityTermsView $entityTermsView
	 * @param StatementGroupListView $statementGroupListView
	 * @param Language $language
	 * @param SiteLinksView $siteLinksView
	 * @param string[] $siteLinkGroups
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		EntityTermsView $entityTermsView,
		StatementGroupListView $statementGroupListView,
		Language $language,
		SiteLinksView $siteLinksView,
		array $siteLinkGroups = array()
	) {
		parent::__construct( $templateFactory, $entityTermsView, $language );

		$this->statementGroupListView = $statementGroupListView;
		$this->siteLinksView = $siteLinksView;
		$this->siteLinkGroups = $siteLinkGroups;
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
		$html .= $this->statementGroupListView->getHtml(
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
			$this->siteLinkGroups
		);
	}

}
