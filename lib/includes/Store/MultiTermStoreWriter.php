<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
class MultiTermStoreWriter implements EntityTermStoreWriter {

	private $oldStore;
	private $newStore;

	public function __construct( EntityTermStoreWriter $oldStore, EntityTermStoreWriter $newStore ) {
		$this->oldStore = $oldStore;
		$this->newStore = $newStore;
	}

	public function saveTermsOfEntity( EntityDocument $entity ) {
		$success = $this->oldStore->saveTermsOfEntity( $entity );
		return $this->newStore->saveTermsOfEntity( $entity ) && $success;
	}

	public function deleteTermsOfEntity( EntityId $entityId ) {
		$success = $this->oldStore->deleteTermsOfEntity( $entityId );
		return $this->newStore->deleteTermsOfEntity( $entityId ) && $success;
	}

}
