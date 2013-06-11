<?php

namespace Wikibase\Lib;

use DataTypes\DataType;
use DataTypes\DataTypeFactory;
use DataValues\IllegalValueException;
use RuntimeException;
use Wikibase\EntityId;
use Wikibase\EntityLookup;
use Wikibase\PropertyBadValueSnak;
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
	 * @param string $languageCode
	 *
	 * @return string[]
	 */
	public function formatSnaks( array $snaks, $languageCode ) {
		$formattedValues = array();

		foreach ( $snaks as $snak ) {
			$formattedValues[] = $this->formatSnak( $snak, $languageCode );
		}

		return $formattedValues;
	}

	private function formatSnak( Snak $snak, $languageCode ) {
		// TODO: replace with a proper registry with a formatter for each snak type.
		// TODO: no value, some value

		if ( $snak instanceof PropertyValueSnak ) {
			return $this->formatPropertyValueSnak( $snak, $languageCode );
		} else if ( $snak instanceof PropertyBadValueSnak ) {
			//NOTE: This essentially restores the original exception that
			//      caused the PropertyBadValueSnak.
			throw new IllegalValueException( $snak->getValueError() );
		}

		return '';
	}

	private function formatPropertyValueSnak( PropertyValueSnak $snak, $languageCode ) {
		$dataValue = $snak->getDataValue();
		$dataTypeId = $this->getDataTypeForProperty( $snak->getPropertyId() );

		return $this->typedValueFormatter->formatToString( $dataValue, $dataTypeId, $languageCode );
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
