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
	 * @param EntityChange $change
	 */
	public function handleChange( EntityChange $change ) {
		list( , $updateType ) = explode( '~', $change->getType() );

		/**
		 * @var Entity $entity
		 */
		$entity = $change->getEntity();

		if ( !$entity ) {
			throw new \MWException( "The provided Change does not contain full entity data!" );
		}

		$id = $change->getEntityId();

		$store = ClientStoreFactory::getStore();
		$entityCache = $store->newEntityLookup(); //TODO: make sure we get an EntityCache instance

		switch ( $updateType ) {
			case 'remove':
				$entityCache->deleteEntity( $id );
				break;
			case 'add':
				if ( $entityCache->hasEntity( $entity->getId() ) === false ) {
					$entityCache->addEntity( $entity );
					break;
				}
				// Else fall through to 'update' case.
			case 'update':
				$entityCache->updateEntity( $entity );
				break;
			case 'restore':
				$entityCache->updateEntity( $entity );
				break;
		}
	}
}
