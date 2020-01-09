<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
class NullEntityTermStoreWriter implements EntityTermStoreWriter {

	/**
	 * @inheritDoc
	 */
	public function saveTermsOfEntity( EntityDocument $entity ) {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function deleteTermsOfEntity( EntityId $entityId ) {
		return false;
	}

}
