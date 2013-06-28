<?php

namespace Wikibase\Lib;

use DataTypes\DataType;
use DataTypes\DataTypeFactory;
use RuntimeException;
use Wikibase\EntityId;
use Wikibase\EntityLookup;
use Wikibase\PropertyValueSnak;
use Wikibase\Snak;

/**
 * Turns a list of Snak objects into a list of corresponding string representations.
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakFormatter {

	/**
	 * @var TypedValueFormatter
	 */
	private $typedValueFormatter;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	public function __construct( PropertyDataTypeLookup $dataTypeLookup,
		TypedValueFormatter $formatter, DataTypeFactory $dataTypeFactory ) {

		$this->dataTypeLookup = $dataTypeLookup;
		$this->typedValueFormatter = $formatter;
		$this->dataTypeFactory = $dataTypeFactory;
	}

	/**
	 * Turns an array of snaks into an array of strings.
	 *
	 * @since 0.4
	 *
	 * @param Snak[] $snaks
	 * @param array $languages
	 *
	 * @return string[]
	 */
	public function formatSnaks( array $snaks, $languages ) {
		$formattedValues = array();

		foreach ( $snaks as $snak ) {
			$formattedValues[] = $this->formatSnak( $snak, $languages );
		}

		return $formattedValues;
	}

	private function formatSnak( Snak $snak, $languages ) {
		if ( $snak instanceof PropertyValueSnak ) {
			return $this->formatPropertyValueSnak( $snak, $languages );
		}

		// TODO: we might want to allow customization here (this happens for NoValue and SomeValue snaks)
		return '';
	}

	private function formatPropertyValueSnak( PropertyValueSnak $snak, $languages ) {
		$dataValue = $snak->getDataValue();
		$dataTypeId = $this->getDataTypeForProperty( $snak->getPropertyId() );

		return $this->typedValueFormatter->formatToString( $dataValue, $dataTypeId, $languages );
	}

	private function getDataTypeForProperty( EntityId $propertyId ) {
		$dataTypeId = $this->dataTypeLookup->getDataTypeIdForProperty( $propertyId );
		$dataType = $this->dataTypeFactory->getType( $dataTypeId );

		if ( $dataType === null ) {
			throw new RuntimeException( "Could not construct DataType with unknown id '$dataTypeId'" );
		}

		return $dataType;
	}

}
