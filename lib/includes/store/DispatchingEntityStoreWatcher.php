<?php

namespace Wikibase;

/**
 * EntityStoreWatcher that dispatches events to more EntityStoreWatchers.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DispatchingEntityStoreWatcher extends GenericEventDispatcher implements EntityStoreWatcher {

	function __construct() {
		parent::__construct( 'Wikibase\EntityStoreWatcher' );
	}

	/**
	 * called when an entity is updated
	 *
	 * @param EntityRevision $entityRevision
	 */
	public function entityUpdated( EntityRevision $entityRevision ) {
		$this->dispatch( 'entityUpdated', $entityRevision );
	}

	/**
	 * called when an entity is deleted
	 *
	 * @param EntityId $entityId
	 */
	public function entityDeleted( EntityId $entityId ) {
		$this->dispatch( 'entityDeleted', $entityId );
	}
}
