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


use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;

/**
 * TypeValidator checks a value's data type.
 *
 * @package Wikibase\Validators
 */
class TypeValidator implements ValueValidator {

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * Constructs a TypeValidator that checks for the given type.
	 *
	 * @param string $type A PHP type name or a class name.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( $type ) {
		$this->type = $type;
	}

	/**
	 * @see ValueValidator::validate()
	 *
	 * @param mixed $value The value to validate
	 *
	 * @return \ValueValidators\Result
	 * @throws \InvalidArgumentException
	 */
	public function validate( $value ) {
		$type = gettype( $value );

		if ( $type === $this->type ) {
			return Result::newSuccess();
		}

		if ( is_object( $value ) ) {
			$type = get_class( $value );

			if ( is_a( $value, $this->type ) ) {
				return Result::newSuccess();
			}
		}

		return Result::newError( array(
			Error::newError( 'Bad type, expected ' . $this->type, null, 'bad-type', array( $this->type, $type ) )
		) );
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