<?php

namespace Wikibase\Repo\Interactors;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;

/**
 * @license GPL-2.0-or-later
 */
class ItemRedirectCreationInteractor extends EntityRedirectCreationInteractor {

	/**
	 * @param EntityDocument $entity
	 *
	 * @throws RedirectCreationException
	 */
	protected function assertEntityIsRedirectable( EntityDocument $entity ) {
		if ( $entity->getType() === Item::ENTITY_TYPE && !$entity->isEmpty() ) {
			throw new RedirectCreationException(
				"Can't create redirect on non empty item " . $entity->getId(),
				'origin-not-empty',
				[ $entity->getId()->serialize() ]
			);
		}
	}

}
