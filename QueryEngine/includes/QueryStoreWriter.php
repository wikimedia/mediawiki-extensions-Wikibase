<?php

namespace Wikibase\QueryEngine;

use Wikibase\Entity;

/**
 * Updater for a query store.
 * Implementing objects provide an interface via which new data can be inserted
 * into the query store, existing data can be updated and existing data can be removed.
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
 * @ingroup WikibaseQueryStore
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface QueryStoreWriter {

	/**
	 * @see QueryStoreUpdater::insertEntity
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 */
	public function insertEntity( Entity $entity );

	/**
	 * @see QueryStoreUpdater::updateEntity
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 */
	public function updateEntity( Entity $entity );

	/**
	 * @see QueryStoreUpdater::deleteEntity
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 */
	public function deleteEntity( Entity $entity );

}
