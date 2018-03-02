<?php

namespace Wikibase\Repo\Validators;

use InvalidArgumentException;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;

/**
 * Validator for checking that a given string is NOT an EntityId.
 * Useful e.g. for preventing property labels that "look like" property IDs.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class NotEntityIdValidator implements ValueValidator {

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var string
	 */
	private $errorCode;

	/**
	 * @var string[]|null List of entity types that are to be forbidden by this validator.
	 */
	private $forbiddenTypes;

	/**
	 * @param EntityIdParser $idParser The parser to use for testing whether a string is an entity ID.
	 * @param string $errorCode The error code to use when this validator fails.
	 * @param string[]|null $forbiddenTypes A list of entity types who's IDs should be considered
	 *        invalid values. If null, all valid entity IDs are considered invalid input.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( EntityIdParser $idParser, $errorCode, array $forbiddenTypes = null ) {
		if ( !is_string( $errorCode ) ) {
			throw new InvalidArgumentException( '$errorCode must be a string' );
		}

		$this->idParser = $idParser;
		$this->errorCode = $errorCode;
		$this->forbiddenTypes = $forbiddenTypes;
	}

	/**
	 * @see ValueValidator::validate()
	 *
	 * @param string $value The value to validate
	 *
	 * @return Result
	 */
	public function validate( $value ) {
		$result = Result::newSuccess();

		try {
			$entityId = $this->idParser->parse( $value );

			if ( $this->forbiddenTypes === null
				|| in_array( $entityId->getEntityType(), $this->forbiddenTypes )
			) {
				// The label looks like a valid ID - we don't like that!
				$error = Error::newError(
					'Looks like an Entity ID: ' . $value,
					null,
					$this->errorCode,
					[ $value ]
				);
				$result = Result::newError( [ $error ] );
			}
		} catch ( EntityIdParsingException $parseException ) {
			// All fine, the parsing did not work, so there is no entity id :)
		}

		return $result;
	}

	/**
	 * @see ValueValidator::setOptions()
	 *
	 * @param array $options
	 *
	 * @codeCoverageIgnore
	 */
	public function setOptions( array $options ) {
		// Do nothing. This method shouldn't even be in the interface.
	}

}
