<?php

namespace Wikibase\QueryEngine\SQLStore\ClaimStore;

use InvalidArgumentException;
use Wikibase\Database\QueryInterface;

/**
 * Interface to the claims table.
 *
 * This is not a "claims store" since it does not store whole claims,
 * merely their id mapping and some other non-claim-value information.
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
class ClaimsTable {

	protected $queryInterface;
	protected $tableName;

	/**
	 * @param QueryInterface $queryInterface
	 * @param string $tableName
	 */
	public function __construct( QueryInterface $queryInterface, $tableName ) {
		$this->queryInterface = $queryInterface;
		$this->tableName = $tableName;
	}

	public function insertClaimRow( ClaimRow $claimRow ) {
		if ( $claimRow->getInternalId() !== null ) {
			throw new InvalidArgumentException( 'Cannot insert a ClaimRow that already has an ID' );
		}

		$this->queryInterface->insert(
			$this->tableName,
			$this->getWriteValues( $claimRow )
		);

		return $this->queryInterface->getInsertId();
	}

	protected function getWriteValues( ClaimRow $claimRow ) {
		return array(
			'guid' => $claimRow->getExternalGuid(),
			'subject_id' => $claimRow->getInternalSubjectId(),
			'property_id' => $claimRow->getInternalPropertyId(),
			'rank' => $claimRow->getRank(),
			'hash' => $claimRow->getHash(),
		);
	}



}
