<?php

namespace Wikibase\View;

use InvalidArgumentException;
use Language;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Services\Statement\Grouper\NullStatementGrouper;
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
	 * @var StatementSectionsView
	 */
	private $statementSectionsView;

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
	 * @param StatementSectionsView $statementSectionsView
	 * @param Language $language
	 * @param SiteLinksView $siteLinksView
	 * @param string[] $siteLinkGroups
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		EntityTermsView $entityTermsView,
		StatementSectionsView $statementSectionsView,
		Language $language,
		SiteLinksView $siteLinksView,
		array $siteLinkGroups
	) {
		parent::__construct( $templateFactory, $entityTermsView, $language );

		$this->statementSectionsView = $statementSectionsView;
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
		// TODO: Group statements into actual sections, including an identifiers section.
		$grouper = new NullStatementGrouper();
		$statementLists = $grouper->groupStatements( $item->getStatements() );
		$html .= $this->statementSectionsView->getHtml( $statementLists );

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
