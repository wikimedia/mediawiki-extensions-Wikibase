<?php

namespace Wikibase\Repo\Validators;

use DataValues\StringValue;
use InvalidArgumentException;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\Repo\CachingCommonsMediaFileNameLookup;

/**
 * Validator for commons media values which checks whether the file in question
 * exists. Doesn't check whether the name is normalized.
 *
 * @license GPL 2+
 * @author Marius Hoch
 */
class CommonsMediaExistsValidator implements ValueValidator {

	/**
	 * @var CachingCommonsMediaFileNameLookup
	 */
	private $fileNameLookup;

	/**
	 * @param CachingCommonsMediaFileNameLookup $fileNameLookup
	 */
	public function __construct( CachingCommonsMediaFileNameLookup $fileNameLookup ) {
		$this->fileNameLookup = $fileNameLookup;
	}

	/**
	 * @see ValueValidator::validate()
	 *
	 * @param StringValue $value
	 *
	 * @return Result
	 * @throws InvalidArgumentException
	 */
	public function validate( $value ) {
		if ( $value instanceof StringValue ) {
			$value = $value->getValue();
		}

		if ( !is_string( $value ) ) {
			throw new InvalidArgumentException( "Expected a StringValue." );
		}

		$actualName = $this->fileNameLookup->normalize( $value );

		$errors = array();

		if ( $actualName === null ) {
			$errors[] = Error::newError(
				"File does not exist: " . $value,
				null,
				'no-such-media',
				array( $value )
			);
		}

		return empty( $errors ) ? Result::newSuccess() : Result::newError( $errors );
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
