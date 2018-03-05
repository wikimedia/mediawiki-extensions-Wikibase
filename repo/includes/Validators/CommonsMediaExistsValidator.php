<?php

namespace Wikibase\Repo\Validators;

use DataValues\StringValue;
use InvalidArgumentException;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\Repo\CachingCommonsMediaFileNameLookup;
use Wikimedia\Assert\Assert;

/**
 * Validator for commons media values which checks whether the file in question
 * exists. Doesn't check whether the name is normalized.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class CommonsMediaExistsValidator implements ValueValidator {

	/**
	 * @var CachingCommonsMediaFileNameLookup
	 */
	private $fileNameLookup;

	public function __construct( CachingCommonsMediaFileNameLookup $fileNameLookup ) {
		$this->fileNameLookup = $fileNameLookup;
	}

	/**
	 * @see ValueValidator::validate()
	 *
	 * @param StringValue|string $value
	 *
	 * @return Result
	 * @throws InvalidArgumentException
	 */
	public function validate( $value ) {
		Assert::parameterType( 'string|DataValues\StringValue', $value, '$value' );

		if ( $value instanceof StringValue ) {
			$value = $value->getValue();
		}

		$actualName = $this->fileNameLookup->lookupFileName( $value );

		$errors = [];

		if ( $actualName === null ) {
			$errors[] = Error::newError(
				"File does not exist: " . $value,
				null,
				'no-such-media',
				[ $value ]
			);
		}

		return empty( $errors ) ? Result::newSuccess() : Result::newError( $errors );
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
