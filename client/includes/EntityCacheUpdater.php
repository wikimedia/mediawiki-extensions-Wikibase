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
		list( $entityType, $updateType ) = explode( '~', $change->getType() );

		/**
		 * @var EntityCache $entityCache
		 * @var Entity $entity
		 */
		$entityCache = EntityCache::singleton();
		$entity = $change->getEntity();

		switch ( $updateType ) {
			case 'remove':
				$entityCache->deleteEntity( $entity );
				break;
			case 'add':
				$entityCache->addEntity( $entity );
				break;
			case 'update':
				$entityCache->updateEntity( $entity );
				break;
		}

		// TODO: handle refresh updates and refresh for other types as well

		if ( $entity->getType() == Item::ENTITY_TYPE ) {
			SiteLinkCache::singleton()->saveLinksOfItem( $entity );
		}
	}

}
