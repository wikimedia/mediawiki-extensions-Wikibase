<?php

namespace Wikibase\QueryEngine\SQLStore\SnakStore;

use RuntimeException;
use Wikibase\Snak;

/**
 * Use case for inserting snaks into the store.
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
class SnakInserter {

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
	 * @param Snak $snak
	 * @param int $snakRole
	 * @param int $internalClaimId
	 * @param int $internalPropertyId
	 */
	public function insertSnak( Snak $snak, $snakRole, $internalClaimId ) {
//		foreach ( $this->snakStores as $snakStore ) {
//			if ( $snakStore->canStore( $snakRow ) ) {
//				$snakStore->storeSnakRow( $snakRow );
//			}
//		}
//
//		throw new RuntimeException( 'Cannot store the snak as there is no SnakStore that can handle it' );
	}

}
