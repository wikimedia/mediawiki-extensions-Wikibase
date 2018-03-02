<?php

namespace Wikibase\Repo\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Repo\GenericEventDispatcher;

/**
 * EntityStoreWatcher that dispatches events to more EntityStoreWatchers.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class DispatchingEntityStoreWatcher extends GenericEventDispatcher implements EntityStoreWatcher {

	public function __construct() {
		parent::__construct( EntityStoreWatcher::class );
	}

	/**
	 * @see EntityStoreWatcher::entityUpdated
	 *
	 * @param EntityRevision $entityRevision
	 */
	public function entityUpdated( EntityRevision $entityRevision ) {
		$this->dispatch( 'entityUpdated', $entityRevision );
	}

	/**
	 * @see EntityStoreWatcher::redirectUpdated
	 *
	 * @param EntityRedirect $entityRedirect
	 * @param int $revisionId
	 */
	public function redirectUpdated( EntityRedirect $entityRedirect, $revisionId ) {
		$this->dispatch( 'redirectUpdated', $entityRedirect, $revisionId );
	}

	/**
	 * @see EntityStoreWatcher::entityDeleted
	 *
	 * @param EntityId $entityId
	 */
	public function entityDeleted( EntityId $entityId ) {
		$this->dispatch( 'entityDeleted', $entityId );
	}

}
