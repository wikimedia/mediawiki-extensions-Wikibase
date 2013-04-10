<?php

namespace Wikibase\QueryEngine\SQLStore\DVHandler;

use DataValues\DataValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\Database\FieldDefinition;
use Wikibase\Database\TableDefinition;
use Wikibase\QueryEngine\SQLStore\DataValueHandler;

/**
 * Represents the mapping between Wikibase\StringValue and
 * the corresponding table in the store.
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
class StringHandler extends DataValueHandler {

	/**
	 * @see DataValueHandler::newDataValueFromValueField
	 *
	 * @since 0.1
	 *
	 * @param $valueFieldValue // TODO: mixed or string?
	 *
	 * @return DataValue
	 */
	public function newDataValueFromValueField( $valueFieldValue ) {
		return new StringValue( $valueFieldValue );
	}

	/**
	 * @see DataValueHandler::getWhereConditions
	 *
	 * @since 0.1
	 *
	 * @param DataValue $value
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public function getWhereConditions( DataValue $value ) {
		if ( !( $value instanceof StringValue ) ) {
			throw new InvalidArgumentException( 'Value is not a StringValue' );
		}

		return array(
			'value' => $value->getValue(),
		);
	}

	/**
	 * @see DataValueHandler::getInsertValues
	 *
	 * @since 0.1
	 *
	 * @param DataValue $value
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public function getInsertValues( DataValue $value ) {
		if ( !( $value instanceof StringValue ) ) {
			throw new InvalidArgumentException( 'Value is not a StringValue' );
		}

		$values = array(
			'value' => $value->getValue(),
		);

		return $values;
	}

}