<?php

namespace Wikibase;

use DataValues\StringValue;
use OutOfBoundsException;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\PropertyNotFoundException;

/**
 * Finds URLs given a list of entities or a list of claims.
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
 * @author Daniel Kinzler
 */
class ReferencedUrlFinder {

	/**
	 * @since 0.4
	 *
	 * @var PropertyDataTypeLookup
	 */
	protected $propertyDataTypeLookup;

	/**
	 * @since 0.4
	 *
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 */
	public function __construct( PropertyDataTypeLookup $propertyDataTypeLookup ) {
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
	}

	/**
	 * @param Snak[] $snaks
	 *
	 * @return string[]
	 */
	public function findSnakLinks( array $snaks ) {
		$foundURLs = array();

		foreach ( $snaks as $snak ) {
			// PropertyValueSnaks might have a value referencing a URL, find those:
			if( $snak instanceof PropertyValueSnak ) {
				try {
					$type = $this->propertyDataTypeLookup->getDataTypeIdForProperty( $snak->getPropertyId() );
				} catch ( OutOfBoundsException $ex ) {
					wfLogWarning( 'No data type known for property ' . $snak->getPropertyId() );
					continue;
				} catch ( PropertyNotFoundException $ex ) {
					wfLogWarning( 'No data type known for unknown property ' . $snak->getPropertyId() );
					continue;
				}
				if ( $type !== 'url' ) {
					continue;
				}

				$snakValue = $snak->getDataValue();

				if( $snakValue === null ) {
					// shouldn't ever run into this, but make sure!
					continue;
				}

				if ( $snakValue instanceof StringValue ) {
					$foundURLs[] = $snakValue->getValue();
				} else {
					wfLogWarning( 'Unexpected value type for url: ' . $snakValue->getType() );
				}
			}
		}

		return array_unique( $foundURLs );
	}

}


