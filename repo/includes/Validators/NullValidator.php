<?php

namespace Wikibase\Repo\Validators;

use InvalidArgumentException;
use ValueValidators\Result;
use ValueValidators\ValueValidator;

/**
 * Dummy validator returning valid for any value
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class NullValidator implements ValueValidator {

	/**
	 * @see ValueValidator::validate()
	 *
	 * @param mixed $value Anything!
	 *
	 * @return Result
	 */
	public function validate( $value ) {
		return Result::newSuccess();
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
