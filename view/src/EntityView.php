<?php

namespace Wikibase\View;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\View\Template\TemplateFactory;

/**
 * Base class for creating views for all different kinds of Wikibase\DataModel\Entity\EntityDocument.
 * For the Wikibase\DataModel\Entity\EntityDocument this basically is what the Parser is for WikitextContent.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author H. Snater < mediawiki at snater.com >
 * @author Daniel Werner
 * @author Daniel Kinzler
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
abstract class EntityView {

	/**
	 * @var TemplateFactory
	 */
	protected $templateFactory;

	/**
	 * @var EntityTermsView
	 */
	private $entityTermsView;

	/**
	 * @var LanguageDirectionalityLookup
	 */
	private $languageDirectionalityLookup;

	/**
	 * @var string
	 */
	protected $languageCode;

	/**
	 * @var TextInjector
	 */
	private $textInjector;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param EntityTermsView $entityTermsView
	 * @param LanguageDirectionalityLookup $languageDirectionalityLookup
	 * @param string $languageCode
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		EntityTermsView $entityTermsView,
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		$languageCode
	) {
		$this->entityTermsView = $entityTermsView;
		$this->languageDirectionalityLookup = $languageDirectionalityLookup;
		$this->languageCode = $languageCode;

		$this->textInjector = new TextInjector();
		$this->templateFactory = $templateFactory;
	}

	/**
	 * Returns the placeholder map build while generating HTML.
	 * The map returned here may be used with TextInjector.
	 *
	 * @return array[] string -> array
	 */
	public function getPlaceholders() {
		return $this->textInjector->getMarkers();
	}

	/**
	 * Builds and returns the HTML representing a whole WikibaseEntity.
	 *
	 * @note: The HTML returned by this method may contain placeholders. Such placeholders can be
	 * expanded with the help of TextInjector::inject() calling back to
	 * EntityViewPlaceholderExpander::getExtraUserLanguages()
	 * @note: In order to keep the list of placeholders small, this calls resetPlaceholders().
	 *
	 * @since 0.1
	 *
	 * @param EntityDocument $entity the entity to render
	 *
	 * @return string HTML
	 */
	public function getHtml( EntityDocument $entity ) {
		$entityId = $entity->getId() ?: 'new'; // if id is not set, use 'new' suffix for css classes

		$html = $this->templateFactory->render( 'wikibase-entityview',
			$entity->getType(),
			$entityId,
			$this->languageCode,
			$this->languageDirectionalityLookup->getDirectionality( $this->languageCode ) ?: 'auto',
			$this->getMainHtml( $entity ),
			$this->getSideHtml( $entity )
		);

		return $html;
	}

	/**
	 * Returns the html used for the title of the page.
	 * @see ParserOutput::setDisplayTitle
	 *
	 * @since 0.5
	 *
	 * @param EntityDocument $entity
	 *
	 * @return string HTML
	 */
	public function getTitleHtml( EntityDocument $entity ) {
		if ( $entity instanceof FingerprintProvider ) {
			return $this->entityTermsView->getTitleHtml(
				$this->languageCode,
				$entity->getFingerprint(),
				$entity->getId()
			);
		}

		return '';
	}

	/**
	 * Builds and returns the HTML to be put into the main container of an entity's HTML structure.
	 *
	 * @param EntityDocument $entity
	 *
	 * @return string HTML
	 */
	abstract protected function getMainHtml( EntityDocument $entity );

	/**
	 * Builds and Returns HTML to put into the sidebar of the entity's HTML structure.
	 *
	 * @param EntityDocument $entity
	 *
	 * @return string HTML
	 */
	abstract protected function getSideHtml( EntityDocument $entity );

	/**
	 * Builds and returns the HTML for the entity's fingerprint.
	 *
	 * @param EntityDocument $entity
	 *
	 * @return string HTML
	 */
	protected function getHtmlForFingerprint( EntityDocument $entity ) {
		$id = $entity->getId();

		if ( $entity instanceof FingerprintProvider ) {
			return $this->entityTermsView->getHtml(
				$this->languageCode,
				$entity->getFingerprint(),
				$id,
				$this->getHtmlForTermBox(),
				$this->textInjector
			);
		}

		return '';
	}

	/**
	 * @return string HTML
	 */
	private function getHtmlForTermBox() {
		// Placeholder for a termbox for the present item.
		// EntityViewPlaceholderExpander must know about the parameters used here.
		return $this->textInjector->newMarker( 'termbox' );
	}

}
