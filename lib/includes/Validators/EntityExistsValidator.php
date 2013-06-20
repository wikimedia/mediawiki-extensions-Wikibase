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


use ValueParsers\ParseException;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\EntityId;
use Wikibase\EntityLookup;
use Wikibase\Lib\EntityIdParser;

/**
 * EntityExistsValidator checks that a given entity exists.
 *
 * @package Wikibase\Validators
 */
class EntityExistsValidator implements ValueValidator {

	/**
	 * @var EntityIdParser|null
	 */
	protected $parser;

	/**
	 * @var EntityLookup
	 */
	protected $lookup;

	/**
	 * @param EntityLookup    $lookup
	 * @param EntityIdParser  $parser If given, this validator will accept string IDs.
	 *        If not, only EntityId objects are acceptable.
	 */
	public function __construct( EntityLookup $lookup, EntityIdParser $parser = null ) {
		$this->parser = $parser;
		$this->lookup = $lookup;
	}

	/**
	 * @see ValueValidator::validate()
	 *
	 * @param string|EntityId $value The ID to validate
	 *
	 * @return \ValueValidators\Result
	 * @throws \InvalidArgumentException
	 */
	public function validate( $value ) {
		if ( is_string( $value ) && $this->parser !== null ) {
			//NOTE: may throw a ParseException
			$value = $this->parser->parse( $value );
		}

		if ( !( $value instanceof EntityId ) ) {
			throw new \InvalidArgumentException( "Expected an EntityId object" );
		}

		if ( !$this->lookup->hasEntity( $value ) ) {
			return Result::newError( array(
				//XXX: we are passing an EntityId as a message parameter here - make sure to turn it into a string later!
				Error::newError( "Entity not found: " . $value, null, 'no-such-entity', array( $value ) ),
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