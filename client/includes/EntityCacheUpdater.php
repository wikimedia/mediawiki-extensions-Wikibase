<?php

namespace Wikibase;
use Title;

/**
 * Handler updates to the entity cache.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityCacheUpdater {

	/**
	 *
	 *
	 * @since 0.1
	 *
	 * @param Change $change
	 */
	public function handleChange( Change $change ) {
		list( $entityType, $updateType ) = explode( '-', $change->getType() );

		/**
		 * @var EntityCache $entityCache;
		 */
		$entityCache = EntityCache::singleton();

		switch ( $updateType ) {
			case 'remove':
				$entityCache->deleteEntity( $change->getEntity() );
		}
	}

	/**
	 * Updates the \Wikibase\LocalItem holding the \Wikibase\Item associated with the change.
	 *
	 * @since 0.1
	 *
	 * @param $changeType
	 * @param Item $item
	 * @param \Title $title
	 */
	protected function updateLocalItem( $changeType, Item $item, Title $title ) {
		$localItem = LocalItem::newFromItem( $item );

		if ( $changeType === 'remove' ) {
			$localItem->remove();
		}
		else {
			$localItem->save();
		}
	}

}
