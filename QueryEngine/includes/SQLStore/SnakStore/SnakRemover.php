<?php

namespace Wikibase\QueryEngine\SQLStore\SnakStore;

use RuntimeException;
use Wikibase\Snak;

/**
 * Use case for removing snaks from the store.
 *
 * TODO: this can be made more efficient by providing the list of snaks
 * the entity has, so deletes can be run just against the relevant
 * data value type specific tables.
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
class SnakRemover {

	/**
	 * @var SnakStore[]
	 */
	protected $snakStores;

	/**
	 * @param SnakStore[] $snakStores
	 */
	public function __construct( array $snakStores ) {
		$this->snakStores = $snakStores;
	}

	/**
	 * @since 0.1
	 *
	 * @param int $internalSubjectId
	 */
	public function removeSnaksOfSubject( $internalSubjectId ) {
		foreach ( $this->snakStores as $snakStore ) {
			$snakStore->removeSnaksOfSubject( $internalSubjectId );
		}
	}

}
