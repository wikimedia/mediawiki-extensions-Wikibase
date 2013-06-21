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
 * StringLengthValidator checks a string's length
 *
 * @package Wikibase\Validators
 */
class StringLengthValidator implements ValueValidator {

	/**
	 * @var int
	 */
	protected $minLength;

	/**
	 * @var int
	 */
	protected $maxLength;

	/**
	 * @var callable
	 */
	protected $measure;

	/**
	 * @param int             $minLength
	 * @param int             $maxLength
	 * @param callable|string $measure The function to use to measure the string's length.
	 *                        Use 'strlen' for byte length and 'mb_strlen' for character length.
	 *                        A callable can be used to provide an alternative measure.
	 */
	public function __construct( $minLength, $maxLength, $measure = 'strlen' ) {
		//TODO: check type
		$this->minLength = $minLength;
		$this->maxLength = $maxLength;

		//TODO: check callable
		$this->measure = $measure;
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
		$length = call_user_func( $this->measure, $value );

		if ( $length < $this->minLength ) {
			// XXX: having to provide an array is quite inconvenient
			return Result::newError( array(
				Error::newError( 'Too short, minimum length is ' . $this->minLength, null, 'too-short', array( $this->minLength, $value ) )
			) );
		}

		if ( $length > $this->maxLength ) {
			return Result::newError( array(
				Error::newError( 'Too long, maximum length is ' . $this->maxLength, null, 'too-long', array( $this->maxLength, $value ) )
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