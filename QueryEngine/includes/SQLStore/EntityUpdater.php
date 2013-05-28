<?php

namespace Wikibase\QueryEngine\SQLStore;

use Wikibase\Entity;

/**
 * Use case for updating entities in the store.
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
 * @ingroup WikibaseSQLStore
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityUpdater {

	private $remover;
	private $inserter;

	/**
	 * @since 0.1
	 *
	 */
	public function __construct( EntityRemover $remover, EntityInserter $inserter ) {
		$this->remover = $remover;
		$this->inserter = $inserter;
	}

	/**
	 * @since 0.1
	 *
	 * @param Entity $entity
	 */
	public function updateEntity( Entity $entity ) {
		$this->remover->removeEntity( $entity );
		$this->inserter->insertEntity( $entity );
	}

}
