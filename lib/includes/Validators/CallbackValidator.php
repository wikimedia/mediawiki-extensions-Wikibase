<?php

namespace Wikibase\Lib;

use ValueValidators\Result;
use ValueValidators\ValueValidator;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class CallbackValidator implements ValueValidator {

	protected $validationCallback;

	public function __construct( $validationCallback ) {
		if ( !is_callable( $validationCallback ) ) {
			// TODO
		}

		$this->validationCallback = $validationCallback;
	}

	/**
	 * @see ValueValidator::validate
	 *
	 * @param mixed $value
	 *
	 * @return Result
	 */
	public function validate( $value ) {
		$valueIsValid = call_user_func( $this->validationCallback, $value );

		if ( $valueIsValid ) {
			return Result::newSuccess();
		}

		return Result::newError( array() );
	}

	/**
	 * @see ValueValidator::setOptions()
	 *
	 * @param array $options
	 */
	public function setOptions( array $options ) {
	}

}
