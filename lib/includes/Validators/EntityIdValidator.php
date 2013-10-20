<?php

namespace Wikibase\Validators;

use InvalidArgumentException;
use ValueParsers\ParseException;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\EntityId;
use Wikibase\Lib\EntityIdParser;

/**
 * EntityIdValidator checks that an entity ID string is well formed and refers to the right type of entity.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
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
	 * @return Result
	 * @throws InvalidArgumentException
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