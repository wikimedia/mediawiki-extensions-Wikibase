<?php
 /**
 *
 * Copyright © 10.06.13 by the authors listed below.
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
 * @license GPL 2+
 * @file
 *
 * @author Daniel Kinzler
 */


namespace Wikibase\Validators;


use DataValues\DataValue;
use DataValues\IllegalValueException;
use InvalidArgumentException;
use ValueValidators\Result;
use ValueValidators\ValueValidator;

/**
 * The DataFieldValidator class allows the validation of a single field of a complex
 * DataValues object based on the DataValue's array representation.
 *
 * @since 0.4
 *
 * @package Wikibase\Validators
 */
class DataFieldValidator implements ValueValidator {

	/**
	 * @var string
	 */
	protected $field;

	/**
	 * @var ValueValidator
	 */
	protected $validator;

	/**
	 * @param string         $field     The field on the target DataValue's array representation to check
	 * @param ValueValidator $validator The validator to apply to the given field
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( $field, ValueValidator $validator ) {
		if ( !is_string( $field ) ) {
			throw new InvalidArgumentException( '$field need to be a string' );
		}

		$this->field = $field;

		$this->validator = $validator;
	}

	/**
	 * @see ValueValidator::validate()
	 *
	 * @param array $data The value to validate
	 *
	 * @return Result
	 * @throws InvalidArgumentException
	 * @throws IllegalValueException
	 */
	public function validate( $data ) {
		if ( !is_array( $data ) ) {
			//XXX: or should this just be reported as invalid?
			throw new InvalidArgumentException( "DataValue is not represented as an array" );
		}

		if ( !array_key_exists( $this->field, $data ) ) {
			//XXX: or should this just be reported as invalid?
			throw new InvalidArgumentException( "DataValue's array representation does not contain the field " . $this->field );
		}

		$fieldValue = $data[$this->field];

		$result = $this->validator->validate( $fieldValue );

		// TODO: include the field name in the error report
		return $result;
	}

	/**
	 * @see ValueValidator::setOptions()
	 *
	 * @param array $options
	 */
	public function setOptions( array $options ) {
		// Do nothing. This method shouldn't even be in the interface.
	}
}