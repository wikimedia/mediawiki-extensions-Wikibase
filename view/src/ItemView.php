<?php

namespace Wikibase\View;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\View\Template\TemplateFactory;

/**
 * Class for creating views for Item instances.
 * For the Item this basically is what the Parser is for WikitextContent.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner
 */
class ItemView extends EntityView {

	/**
	 * @var StatementSectionsView
	 */
	private $statementSectionsView;

	/**
	 * @var SiteLinksView
	 */
	private $siteLinksView;

	/**
	 * @var string[]
	 */
	private $siteLinkGroups;

	/**
	 * @var LocalizedTextProvider
	 */
	private $textProvider;

	/**
	 * @see EntityView::__construct
	 *
	 * @param TemplateFactory $templateFactory
	 * @param EntityTermsView $entityTermsView
	 * @param LanguageDirectionalityLookup $languageDirectionalityLookup
	 * @param StatementSectionsView $statementSectionsView
	 * @param string $languageCode
	 * @param SiteLinksView $siteLinksView
	 * @param string[] $siteLinkGroups
	 * @param LocalizedTextProvider $textProvider
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		EntityTermsView $entityTermsView,
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		StatementSectionsView $statementSectionsView,
		$languageCode,
		SiteLinksView $siteLinksView,
		array $siteLinkGroups,
		LocalizedTextProvider $textProvider
	) {
		parent::__construct( $templateFactory, $entityTermsView, $languageDirectionalityLookup, $languageCode );

		$this->statementSectionsView = $statementSectionsView;
		$this->siteLinksView = $siteLinksView;
		$this->siteLinkGroups = $siteLinkGroups;
		$this->textProvider = $textProvider;
	}

	/**
	 * @see EntityView::getMainHtml
	 *
	 * @param EntityDocument $item
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	protected function getMainHtml( EntityDocument $item ) {
		if ( !( $item instanceof StatementListProvider ) ) {
			throw new InvalidArgumentException( '$item must be a StatementListProvider' );
		}

		$html = $this->getHtmlForTerms( $item )
			. $this->templateFactory->render( 'wikibase-toc' )
			. $this->statementSectionsView->getHtml( $item->getStatements() );

		return $html;
	}

	/**
	 * @see EntityView::getSideHtml
	 *
	 * @param EntityDocument $entity
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	protected function getSideHtml( EntityDocument $entity ) {
		if ( !( $entity instanceof Item ) ) {
			throw new InvalidArgumentException( '$item must be an Item' );
		}

		return $this->getHtmlForPageImage()
			. $this->getHtmlForSiteLinks( $entity );
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's site-links.
	 *
	 * @since 0.1
	 *
	 * @param Item $item the entity to render
	 *
	 * @return string HTML
	 */
	private function getHtmlForSiteLinks( Item $item ) {
		return $this->siteLinksView->getHtml(
			$item->getSiteLinkList()->toArray(),
			$item->getId(),
			$this->siteLinkGroups
		);
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's page image.
	 *
	 * @return string
	 */
	private function getHtmlForPageImage() {
		$helpText = $this->textProvider->get( 'wikibase-pageimage-helptext' );
		return $this->templateFactory->render(
			'wikibase-pageimage',
			htmlspecialchars( $helpText )
		);
	}

}
