<?php

namespace Wikibase\DataAccess;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityStoreWatcher;

/**
 * Delegates entity data changed events to multiple configured EntityStoreWatcher instances.
 *
 * @license GPL-2.0+
 */
class MultiEntityStoreWatcher implements EntityStoreWatcher {

	/**
	 * @var EntityStoreWatcher[]
	 */
	private $watchers;

	/**
	 * @param EntityStoreWatcher[] $watchers
	 */
	public function __construct( array $watchers ) {
		$this->watchers = $watchers;
	}

	/**
	 * @see EntityStoreWatcher::entityUpdated
	 *
	 * @param EntityRevision $entityRevision
	 */
	public function entityUpdated( EntityRevision $entityRevision ) {
		foreach ( $this->watchers as $watcher ) {
			$watcher->entityUpdated( $entityRevision );
		}
	}

	/**
	 * @see EntityStoreWatcher::redirectUpdated
	 *
	 * @param EntityRedirect $entityRedirect
	 * @param int $revisionId
	 */
	public function redirectUpdated( EntityRedirect $entityRedirect, $revisionId ) {
		foreach ( $this->watchers as $watcher ) {
			$watcher->redirectUpdated( $entityRedirect, $revisionId );
		}
	}

	/**
	 * @see EntityStoreWatcher::entityDeleted
	 *
	 * @param EntityId $entityId
	 */
	public function entityDeleted( EntityId $entityId ) {
		foreach ( $this->watchers as $watcher ) {
			$watcher->entityDeleted( $entityId );
		}
	}

}
