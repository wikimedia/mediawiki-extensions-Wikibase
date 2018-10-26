<?php

namespace Wikibase\View;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\View\Template\TemplateFactory;

/**
 * Base class for creating views for all different kinds of Wikibase\DataModel\Entity\EntityDocument.
 * If you want a simple interface instead see EntityDocumentView.
 *
 * @license GPL-2.0-or-later
 */
abstract class EntityView implements EntityDocumentView {

	/**
	 * @var TemplateFactory
	 */
	protected $templateFactory;

	/**
	 * @var LanguageDirectionalityLookup
	 */
	private $languageDirectionalityLookup;

	/**
	 * @var string
	 */
	protected $languageCode;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param LanguageDirectionalityLookup $languageDirectionalityLookup
	 * @param string $languageCode
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		$languageCode
	) {
		$this->languageDirectionalityLookup = $languageDirectionalityLookup;
		$this->languageCode = $languageCode;

		$this->templateFactory = $templateFactory;
	}

	protected function renderEntityView( EntityDocument $entity ) {
		$entityId = $entity->getId() ?: 'new'; // if id is not set, use 'new' suffix for css classes

		return $this->templateFactory->render(
			'wikibase-entityview',
			$entity->getType(),
			$entityId,
			$this->languageCode,
			$this->languageDirectionalityLookup->getDirectionality( $this->languageCode ) ?: 'auto',
			$this->getMainHtml( $entity ),
			$this->getSideHtml( $entity )
		);
	}

	/**
	 * Returns the html used for the title of the page.
	 * @see ParserOutput::setDisplayTitle
	 *
	 * @param EntityDocument $entity
	 *
	 * @return string HTML
	 */
	abstract public function getTitleHtml( EntityDocument $entity );

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

}
