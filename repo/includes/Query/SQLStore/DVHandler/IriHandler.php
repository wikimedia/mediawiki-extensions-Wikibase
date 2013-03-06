<?php

namespace Wikibase\Repo\Query\SQLStore\DVHandler;

use Wikibase\Repo\Query\SQLStore\DataValueHandler;
use Wikibase\Repo\Database\TableDefinition;
use Wikibase\Repo\Database\FieldDefinition;
use Wikibase\Repo\Database\FieldDefinition as FD;
use DataValues\DataValue;
use DataValues\IriValue;
use InvalidArgumentException;

/**
 * Represents the mapping between DataValues\IriValue and
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
 * @since wd.qe
 *
 * @file
 * @ingroup WikibaseSQLStore
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class IriHandler extends DataValueHandler {

	/**
	 * @see DataValueHandler::getTableDefinition
	 *
	 * @since wd.qe
	 *
	 * @return TableDefinition
	 */
	public function getTableDefinition() {
		$fields = array(
			new FieldDefinition( 'scheme', FD::TYPE_TEXT, FD::NOT_NULL ),
			new FieldDefinition( 'fragment', FD::TYPE_TEXT, FD::NOT_NULL ),
			new FieldDefinition( 'query', FD::TYPE_TEXT, FD::NOT_NULL ),
			new FieldDefinition( 'hierp', FD::TYPE_TEXT, FD::NOT_NULL ),

			new FieldDefinition( 'iri', FD::TYPE_TEXT, FD::NOT_NULL ),
			new FieldDefinition( 'json', FD::TYPE_TEXT, FD::NOT_NULL ),
		);

		return new TableDefinition( 'iri', $fields );
	}

	/**
	 * @see DataValueHandler::getValueFieldName
	 *
	 * @since wd.qe
	 *
	 * @return string
	 */
	public function getValueFieldName() {
		return 'json';
	}

	/**
	 * @see DataValueHandler::getSortFieldName
	 *
	 * @since wd.qe
	 *
	 * @return string
	 */
	public function getSortFieldName() {
		return 'iri';
	}

	/**
	 * @see DataValueHandler::getLabelFieldName
	 *
	 * @since wd.qe
	 *
	 * @return string|null
	 */
	public function getLabelFieldName() {
		return 'iri';
	}

	/**
	 * @see DataValueHandler::newDataValueFromValueField
	 *
	 * @since wd.qe
	 *
	 * @param $valueFieldValue // TODO: mixed or string?
	 *
	 * @return DataValue
	 */
	public function newDataValueFromValueField( $valueFieldValue ) {
		return IriValue::newFromArray( json_decode( $valueFieldValue, true ) );
	}

	/**
	 * @see DataValueHandler::getWhereConditions
	 *
	 * @since wd.qe
	 *
	 * @param DataValue $value
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public function getWhereConditions( DataValue $value ) {
		if ( !( $value instanceof IriValue ) ) {
			throw new InvalidArgumentException( 'Value is not a IriValue' );
		}

		return array(
			// Note: the code in this package is not dependent on MW.
			// So do not replace this with FormatJSON::encode.
			'json' => json_encode( $value->getArrayValue() ),
		);
	}

	/**
	 * @see DataValueHandler::getInsertValues
	 *
	 * @since wd.qe
	 *
	 * @param DataValue $value
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public function getInsertValues( DataValue $value ) {
		if ( !( $value instanceof IriValue ) ) {
			throw new InvalidArgumentException( 'Value is not a IriValue' );
		}

		$values = array(
			'scheme' => $value->getScheme(),
			'fragment' => $value->getFragment(),
			'query' => $value->getQuery(),
			'hierp' => $value->getHierarchicalPart(),

			'iri' => $value->getValue(),

			// Note: the code in this package is not dependent on MW.
			// So do not replace this with FormatJSON::encode.
			'json' => json_encode( $value->getArrayValue() ),
		);

		return $values;
	}

}