<?php

namespace Wikibase\View;

use Wikibase\DataModel\Entity\EntityDocument;

/**
 * Default Class for creating meta tags
 *
 * To be used in the absence of a specific EntityMetaTagsCreator
 * Always returns the title meta tag set to the EntityID without a description
 *
 * @license GPL-2.0-or-later
 */
class DefaultMetaTagsCreator implements EntityMetaTagsCreator {

	/**
	 * Creates an entity meta tags array with keys as follows:
	 *  array['title']    string The title set as the EntityID
	 * @param EntityDocument $entity
	 *
	 * @return array An entity meta tags array (See above)
	 */
	public function getMetaTags( EntityDocument $entity ): array {
		$metaTags = [
			'title' => $entity->getId()->getSerialization(),
		];

		return $metaTags;
	}

}
