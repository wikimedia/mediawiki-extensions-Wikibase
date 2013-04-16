<?php

namespace Wikibase\QueryEngine\SQLStore\SnakStore;

use InvalidArgumentException;
use Wikibase\Database\QueryInterface;
use Wikibase\Database\TableDefinition;

/**
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
class ValuelessSnakStore extends SnakStore {

	protected $queryInterface;
	protected $tableName;
<<<<<<< HEAD

	public function __construct( QueryInterface $queryInterface, $tableName ) {
		$this->queryInterface = $queryInterface;
		$this->tableName = $tableName;
	}

	public function canStore( SnakRow $snakRow ) {
		return $snakRow instanceof ValuelessSnakRow;
=======
	protected $internalSnakType;

	public function __construct( QueryInterface $queryInterface, $tableName, $internalSnakType ) {
		$this->queryInterface = $queryInterface;
		$this->tableName = $tableName;
		$this->internalSnakType = $internalSnakType;
	}

	public function canStore( SnakRow $snakRow ) {
		return $snakRow instanceof ValuelessSnakRow
			&& $snakRow->getInternalSnakType() === $this->internalSnakType;
>>>>>>> 07485f714592ca0dcc27c5af2d0628de8bb3f56f
	}

	public function storeSnakRow( SnakRow $snakRow ) {
		if ( !$this->canStore( $snakRow ) ) {
<<<<<<< HEAD
			throw new InvalidArgumentException( 'Can only store ValuelessSnakRow in ValuelessSnakStore' );
=======
			throw new InvalidArgumentException(
				"Can only store ValuelessSnakRow with internal snak type '$this->internalSnakType' in ValuelessSnakStore"
			);
>>>>>>> 07485f714592ca0dcc27c5af2d0628de8bb3f56f
		}

		/**
		 * @var ValuelessSnakRow $snakRow
		 */
		$this->queryInterface->insert(
			$this->tableName,
			array(
				'claim_id' => $snakRow->getInternalClaimId(),
				'property_id' => $snakRow->getInternalPropertyId(),
				'snak_type' => $snakRow->getInternalSnakType(),
				'snak_role' => $snakRow->getSnakRole(),
			)
		);
	}

}
