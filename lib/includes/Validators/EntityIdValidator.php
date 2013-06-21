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
use Wikibase\Lib\EntityIdParser;

/**
 * EntityIdValidator checks that an entity ID string is well formed and refers to the right type of entity.
 *
 * @package Wikibase\Validators
 */
class EntityIdValidator implements ValueValidator {

	/**
	 * @var EntityIdParser
	 */
	protected $parser;

	/**
	 * @var array|null
	 */
	protected $allowedTypes;

	/**
	 * @param EntityIdParser $parser
	 * @param array|null     $allowedTypes
	 */
	public function __construct( EntityIdParser $parser, array $allowedTypes = null ) {
		$this->parser = $parser;
		$this->allowedTypes = $allowedTypes;
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
		try {
			/* @var EntityId $id */
			$id = $this->parser->parse( $value );

			if ( $this->allowedTypes !== null
				&& !in_array( $id->getEntityType(), $this->allowedTypes ) ) {

				return Result::newError( array(
					Error::newError( "Bad entity type: " . $id->getEntityType(), null, 'bad-entity-type', array( $id->getEntityType() ) ),
				) );
			}
		} catch ( ParseException $ex ) {
			return Result::newError( array(
				Error::newError( "Bad entity id: " . $value, null, 'bad-entity-id', array( $value ) ),
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