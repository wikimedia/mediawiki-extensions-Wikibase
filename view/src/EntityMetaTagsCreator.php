<?php

namespace Wikibase\View;

use Wikibase\DataModel\Entity\EntityDocument;

/**
 * Interface for creating meta tags (e.g. title and description) for all different kinds of EntityDocument.
 *
 * @license GPL-2.0-or-later
 */
interface EntityMetaTagsCreator {

	/**
	 * Creates an entity meta tags array with keys as follows:
	 *  array['title'] string The title e.g. for the html page title of an entity.
	 *  array['description'] string|null The description which may not always be set.
	 *
	 * @param EntityDocument $entity
	 * @return array An entity meta tags array (See above)
	 */
	public function getMetaTags( EntityDocument $entity ): array;

}
