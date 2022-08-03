<?php

namespace Wikibase\View;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\View\Template\TemplateFactory;

/**
 * Class for creating views for Item instances.
 * For the Item this basically is what the Parser is for WikitextContent.
 *
 * @license GPL-2.0-or-later
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
	 * @var CacheableEntityTermsView
	 */
	private $entityTermsView;

	/**
	 * @see EntityView::__construct
	 *
	 * @param TemplateFactory $templateFactory
	 * @param CacheableEntityTermsView $entityTermsView
	 * @param LanguageDirectionalityLookup $languageDirectionalityLookup
	 * @param StatementSectionsView $statementSectionsView
	 * @param string $languageCode
	 * @param SiteLinksView $siteLinksView
	 * @param string[] $siteLinkGroups
	 * @param LocalizedTextProvider $textProvider
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		CacheableEntityTermsView $entityTermsView,
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		StatementSectionsView $statementSectionsView,
		$languageCode,
		SiteLinksView $siteLinksView,
		array $siteLinkGroups,
		LocalizedTextProvider $textProvider
	) {
		parent::__construct( $templateFactory, $languageDirectionalityLookup, $languageCode );

		$this->statementSectionsView = $statementSectionsView;
		$this->siteLinksView = $siteLinksView;
		$this->siteLinkGroups = $siteLinkGroups;
		$this->textProvider = $textProvider;
		$this->entityTermsView = $entityTermsView;
	}

	/**
	 * @inheritDoc
	 */
	public function getTitleHtml( EntityDocument $entity ) {
		if ( $entity instanceof LabelsProvider ) {
			return $this->entityTermsView->getTitleHtml(
				$entity->getId()
			);
		}

		return '';
	}

	/**
	 * Builds and returns the main content representing a whole WikibaseEntity
	 *
	 * @param EntityDocument $entity the entity to render
	 * @param int $revision The revision of the entity to render
	 *
	 * @return ViewContent
	 */
	public function getContent( EntityDocument $entity, $revision ): ViewContent {
		return new ViewContent(
			$this->renderEntityView( $entity ),
			$this->entityTermsView->getPlaceholders( $entity, $revision, $this->languageCode )
		);
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
		return $this->templateFactory->render(
			'wikibase-pageimage',
			$this->textProvider->getEscaped( 'wikibase-pageimage-helptext' )
		);
	}

	/**
	 * Builds and returns the HTML for the entity's fingerprint.
	 *
	 * @param EntityDocument $entity
	 *
	 * @return string HTML
	 */
	protected function getHtmlForTerms( EntityDocument $entity ) {
		$id = $entity->getId();

		if ( $entity instanceof LabelsProvider && $entity instanceof DescriptionsProvider ) {
			return $this->entityTermsView->getHtml(
				$this->languageCode,
				$entity->getLabels(),
				$entity->getDescriptions(),
				$entity instanceof AliasesProvider ? $entity->getAliasGroups() : null,
				$id
			);
		}

		return '';
	}

}
