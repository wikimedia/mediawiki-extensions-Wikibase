<?php

namespace Wikibase\QueryEngine\SQLStore\SnakStore;

use Wikibase\Database\QueryInterface;
use Wikibase\Database\TableDefinition;
use Wikibase\PropertyNoValueSnak;
use Wikibase\QueryEngine\SQLStore\SnakRow;

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
class NoValueSnakStore extends SnakStore {

	protected $queryInterface;
	protected $tableName;

	public function __construct( QueryInterface $queryInterface, $tableName ) {
		$this->queryInterface = $queryInterface;
		$this->tableName = $tableName;
	}

	public function canStore( SnakRow $storeSnak ) {
		return $storeSnak->getSnak() instanceof PropertyNoValueSnak;
	}

	public function storeSnakRow( SnakRow $snakRow ) {
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
