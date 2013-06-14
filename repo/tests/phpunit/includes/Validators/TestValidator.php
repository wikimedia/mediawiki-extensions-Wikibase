<?php

namespace Wikibase\Test;

use DataTypes\DataType;
use DataTypes\DataTypeFactory;
use DataValues\DataValue;
use DataValues\StringValue;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\StringValidator;
use ValueValidators\ValueValidator;
use Wikibase\Claim;
use Wikibase\EntityId;
use Wikibase\Lib\InMemoryDataTypeLookup;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Property;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\Reference;
use Wikibase\ReferenceList;
use Wikibase\References;
use Wikibase\Snak;
use Wikibase\SnakList;
use Wikibase\Statement;
use Wikibase\Validators\DataValueValidator;
use Wikibase\Validators\SnakValidator;

/**
 * A simple validator for use in unit tests.
 * Checks a string value against a regular expression.
 * If the value is a DataValue object, it's native representation
 * ("array value") will be checked.
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
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class TestValidator implements ValueValidator {
	protected $regex;

	public function __construct( $regex ) {
		$this->regex = $regex;
	}

	/**
	 * @return Result
	 */
	public function validate( $value ) {
		if ( $value instanceof DataValue ) {
			$value = $value->getArrayValue();
		}

		if ( preg_match( $this->regex, $value ) ) {
			return Result::newSuccess();
		} else {
			return Result::newError( array(
				Error::newError( "doesn't match " . $this->regex )
			) );
		}
	}

	/**
	 * @param array $options
	 */
	public function setOptions( array $options ) {
		// noop
	}
}
