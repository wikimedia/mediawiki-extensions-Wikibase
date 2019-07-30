<?php

namespace Wikibase\Repo\Validators;

use DataValues\StringValue;
use InvalidArgumentException;
use Title;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikimedia\Assert\Assert;

/**
 * Validator for wiki links.
 * Checks whether the page title exists.
 *
 * @license GPL-2.0-or-later
 */
class WikiLinkExistsValidator implements ValueValidator {

	/**
	 * @var int
	 */
	private $namespace;

	/**
	 * @param int $namespace
	 */
	public function __construct( $namespace ) {
		$this->namespace = $namespace;
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

		$errors = [];
		$title = Title::makeTitleSafe( $this->namespace, $value );
		if ( $title === null || !$title->exists() ) {
			$errors[] = Error::newError(
				"Page does not exist: " . $value,
				null,
				'page-not-exists',
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
