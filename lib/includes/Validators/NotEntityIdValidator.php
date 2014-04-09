<?php

namespace Wikibase\Validators;

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
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class NotEntityIdValidator implements ValueValidator {

	/**
	 * @var array|null List of entity types that are to be forbidden by this validator.
	 */
	protected $forbiddenTypes;

	/**
	 * @var EntityIdParser
	 */
	protected $idParser;

	/**
	 * @param EntityIdParser $idParser The parser to use for testing whether a string is an entity ID.
	 * @param null|array $forbiddenTypes A list of entity types who's IDs should be considered
	 *        invalid values. If null, all valid entity IDs are considered invaliud input.
	 */
	public function __construct( EntityIdParser $idParser, $forbiddenTypes = null ) {
		$this->idParser = $idParser;
		$this->forbiddenTypes = $forbiddenTypes;
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
		$result = Result::newSuccess();

		try {
			$entityId = $this->idParser->parse( $value );

			if ( $this->forbiddenTypes === null
				|| in_array( $entityId->getEntityType(), $this->forbiddenTypes )
			) {
				// The label is a valid ID - we don't like that!
				$error = Error::newError( 'Label looks like an Entity ID: ' . $value, 'label', 'label-no-entityid', array( $value ) );
				$result = Result::newError( array( $error ) );
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
	 */
	public function setOptions( array $options ) {
		// Do nothing. This method shouldn't even be in the interface.
	}
}