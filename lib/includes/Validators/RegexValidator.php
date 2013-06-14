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
 * RegexValidator checks a string against a regular expression.
 *
 * @package Wikibase\Validators
 */
class RegexValidator implements ValueValidator {

	/**
	 * @var string
	 */
	protected $expression;

	/**
	 * @var bool
	 */
	protected $inverse;

	/**
	 * @param string  $expression
	 * @param bool    $inverse
	 */
	public function __construct( $expression, $inverse = false ) {
		//TODO: check type
		$this->expression = $expression;
		$this->inverse = $inverse;
	}

	/**
	 * @see ValueValidator::validate()
	 *
	 * @param string $value The value to validate
	 *
	 * @return \ValueValidators\Result
	 * @throws \InvalidArgumentException
	 */
	public function validate( $value ) {
		$match = preg_match( $this->expression, $value );

		if ( $match === 0 && !$this->inverse ) {
			// XXX: having to provide an array is quite inconvenient
			return Result::newError( array(
				//TODO: How to localize the message? Provide an error key and parameters?
				Error::newError( 'Pattern match failed: ' . $this->expression )
			) );
		}

		if ( $match === 1 && $this->inverse ) {
			// XXX: having to provide an array is quite inconvenient
			return Result::newError( array(
				//TODO: How to localize the message? Provide an error key and parameters?
				Error::newError( 'Negative pattern matched: ' . $this->expression )
			) );
		}

		return Result::newSuccess();
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