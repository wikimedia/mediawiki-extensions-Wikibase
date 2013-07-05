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


use DataTypes\DataTypeFactory;
use DataValues\DataValue;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\Claim;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\PropertyValueSnak;
use Wikibase\Reference;
use Wikibase\References;
use Wikibase\Snak;
use Wikibase\Statement;

/**
 * A CompositeValidator uses a list of sub-validators to validate the data.
 * It does not implement any validation logic directly.
 *
 * @package Wikibase\Validators
 */
class CompositeValidator implements ValueValidator {

	/**
	 * @var ValueValidator[]
	 */
	protected $validators;

	/**
	 * @var bool
	 */
	protected $failFast;

	/**
	 * @param ValueValidator[] $validators
	 * @param bool $failFast If true, validation will be aborted after the first sub validator fails.
	 */
	public function __construct( array $validators, $failFast = true ) {
		//TODO: make sure they are all instances of ValueValidator
		$this->validators = $validators;
		$this->failFast = $failFast;
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
		$result = Result::newSuccess();

		foreach ( $this->validators as $validator ) {
			$subResult = $validator->validate( $value );

			if ( !$subResult->isValid() ) {
				if ( $this->failFast ) {
					return $subResult;
				} else {
					$result = Result::merge( $result, $subResult );
				}
			}
		}

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