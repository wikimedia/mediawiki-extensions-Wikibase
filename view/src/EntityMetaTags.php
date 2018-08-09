<?php

namespace Wikibase\View;

/**
 * Base class for creating meta tags (e.g. title and description) for all different kinds of Wikibase\DataModel\Entity\EntityDocument.
 *
 * @license GPL-2.0-or-later
 */
interface EntityMetaTags {

	public function getMetaTags( $entity );

}
