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
use ValueValidators\ValueValidator;

/**
 * The DataValueValidator class allows the validation of the plain value
 * of a simple DataValues object based on the DataValue's array representation.
 *
 * @package Wikibase\Validators
 */
class DataValueValidator implements ValueValidator {

	/**
	 * @var ValueValidator
	 */
	protected $validator;

	/**
	 * @param ValueValidator $validator The validator to apply to the given field
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( ValueValidator $validator ) {
		$this->validator = $validator;
	}

	/**
	 * @see ValueValidator::validate()
	 *
	 * @param DataValue $value The value to validate
	 *
	 * @return \ValueValidators\Result
	 * @throws \InvalidArgumentException
	 */
	public function validate( $value ) {
		if ( !( $value instanceof DataValue ) ) {
			throw new \InvalidArgumentException( 'DataValue expected' );
		}

		$arrayValue = $value->getArrayValue();
		$result = $this->validator->validate( $arrayValue );
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