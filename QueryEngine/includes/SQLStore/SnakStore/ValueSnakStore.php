<?php

namespace Wikibase\QueryEngine\SQLStore\SnakStore;

use InvalidArgumentException;
use OutOfBoundsException;
use Wikibase\Database\QueryInterface;
use Wikibase\QueryEngine\SQLStore\DataValueHandler;

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
class ValueSnakStore extends SnakStore {

	protected $queryInterface;
	protected $dataValueHandlers;
	protected $snakRole;

	/**
	 * The array of DataValueHandlers must have DataValue types as array keys pointing to
	 * the corresponding DataValueHandler.
	 *
	 * @param QueryInterface $queryInterface
	 * @param DataValueHandler[] $dataValueHandlers
	 * @param int $supportedSnakRole
	 */
	public function __construct( QueryInterface $queryInterface, array $dataValueHandlers, $supportedSnakRole ) {
		$this->queryInterface = $queryInterface;
		$this->dataValueHandlers = $dataValueHandlers;
		$this->snakRole = $supportedSnakRole;
	}

	public function canStore( SnakRow $snakRow ) {
		return ( $snakRow instanceof ValueSnakRow )
			&& $this->snakRole === $snakRow->getSnakRole();
	}

	/**
	 * @param string $dataValueType
	 *
	 * @return DataValueHandler
	 * @throws OutOfBoundsException
	 */
	protected function getDataValueHandler( $dataValueType ) {
		if ( !array_key_exists( $dataValueType, $this->dataValueHandlers ) ) {
			throw new OutOfBoundsException( "There is no DataValueHandler set for '$dataValueType'" );
		}

		return $this->dataValueHandlers[$dataValueType];
	}

	public function storeSnakRow( SnakRow $snakRow ) {
		if ( !$this->canStore( $snakRow ) ) {
			throw new InvalidArgumentException( 'Can only store ValueSnakRow of the right snak type in ValueSnakStore' );
		}

		/**
		 * @var ValueSnakRow $snakRow
		 */
		$dataValueHandler = $this->getDataValueHandler( $snakRow->getValue()->getType() );

		$tableName = $dataValueHandler->getDataValueTable()->getTableDefinition()->getName();

		$insertValues = array_merge(
			array(
				'claim_id' => $snakRow->getInternalClaimId(),
				'property_id' => $snakRow->getInternalPropertyId(),
				'subject_id' => $snakRow->getInternalSubjectId(),
			),
			$dataValueHandler->getInsertValues( $snakRow->getValue() )
		);

		$this->queryInterface->insert(
			$tableName,
			$insertValues
		);
	}

	public function removeSnaksOfSubject( $internalSubjectId ) {
		foreach ( $this->dataValueHandlers as $dvHandler ) {
			$this->queryInterface->delete(
				$dvHandler->getDataValueTable()->getTableDefinition()->getName(),
				array( 'subject_id' => $internalSubjectId )
			);
		}
	}

}
