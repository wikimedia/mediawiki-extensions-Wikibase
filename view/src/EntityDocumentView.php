<?php

namespace Wikibase\View;

use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\RepoHooks;

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
	 *
	 * @return string HTML
	 */
	public function getHtml( EntityDocument $entity );

	/**
	 * Returns the html used for the title of the page.
	 * @see ParserOutput::setDisplayTitle
	 *
	 * @param EntityDocument $entity
	 *
	 * @return string HTML
	 */
	public function getTitleHtml( EntityDocument $entity );

	/**
	 * Information about placeholder to store in the (cached) ParserOutput
	 * object for later use in the page output
	 * @see ParserOutput::setExtensionData()
	 * @see RepoHooks::onOutputPageParserOutput()
	 *
	 * @param EntityDocument $entity
	 * @return array
	 */
	public function getPlaceholderInformation( EntityDocument $entity );

}
