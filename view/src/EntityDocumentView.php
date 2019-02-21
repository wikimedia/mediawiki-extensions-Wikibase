<?php

namespace Wikibase\View;

use Wikibase\DataModel\Entity\EntityDocument;

/**
 * Interface for creating views for all different kinds of Wikibase\DataModel\Entity\EntityDocument.
 *
 * @license GPL-2.0-or-later
 */
interface EntityDocumentView {

	/**
	 * Builds and returns the HTML representing a whole WikibaseEntity.
	 *
	 * @param EntityDocument $entity the entity to render
	 * @param int $revision the revision of the entity to render
	 *
	 * @return ViewContent
	 */
	public function getContent( EntityDocument $entity, $revision ): ViewContent;

	/**
	 * Returns the html used for the title of the page.
	 * @see \ParserOutput::setDisplayTitle()
	 *
	 * @param EntityDocument $entity
	 *
	 * @return string HTML
	 */
	public function getTitleHtml( EntityDocument $entity );

}
