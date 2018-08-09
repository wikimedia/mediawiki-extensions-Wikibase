<?php

namespace Wikibase\View;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Base class for creating meta tags (e.g. title and description) for all different kinds of Wikibase\DataModel\Entity\EntityDocument.
 *
 * @license GPL-2.0-or-later
 */
abstract class EntityMetaTags {

	protected function getTitleText( EntityDocument $entity ) {
		$titleText = null;

		$entityId = $entity->getId();

		if ( $entityId instanceof EntityId ) {
			$titleText = $entityId->getSerialization();
		}

		return $titleText;
	}

}
