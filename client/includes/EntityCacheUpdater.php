<?php

namespace Wikibase;
use Title;

/**
 * Handler updates to the entity cache.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
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
	 * Update the entity cache to reflect the provided change.
	 *
	 * @since 0.1
	 *
	 * @param Change $change
	 */
	public function handleChange( Change $change ) {
		list( $entityType, $updateType ) = explode( '~', $change->getType() );

		$store = ClientStoreFactory::getStore();
		$entityCache = $store->newEntityCache();

		/**
		 * @var Entity $entity
		 */
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
			$store->newSiteLinkCache()->saveLinksOfItem( $entity );
		}
	}

}
