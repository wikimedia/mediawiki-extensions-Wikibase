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

	/**
	 * The array of DataValueHandlers must have DataValue types as array keys pointing to
	 * the corresponding DataValueHandler.
	 *
	 * @param QueryInterface $queryInterface
	 * @param DataValueHandler[] $dataValueHandlers
	 */
	public function __construct( QueryInterface $queryInterface, array $dataValueHandlers ) {
		$this->queryInterface = $queryInterface;
		$this->dataValueHandlers = $dataValueHandlers;
	}

	public function canStore( SnakRow $snakRow ) {
		return $snakRow instanceof ValueSnakRow;
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
		if ( !( $snakRow instanceof ValueSnakRow ) ) {
			throw new InvalidArgumentException( 'Can only store ValueSnakRow in ValueSnakStore' );
		}

		$dataValueHandler = $this->getDataValueHandler( $snakRow->getValue()->getType() );

		$this->queryInterface->insert(
			$dataValueHandler->getDataValueTable()->getTableDefinition()->getName(),
			$dataValueHandler->getInsertValues( $snakRow->getValue() )
		);
	}

}
