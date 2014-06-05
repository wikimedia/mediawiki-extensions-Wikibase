<?php

namespace Wikibase\Repo\Localizer;

use Exception;
use InvalidArgumentException;
use Message;
use ValueFormatters\ValueFormatter;
use Wikibase\ChangeOp\ChangeOpValidationException;
use Wikibase\Lib\Localizer\ExceptionLocalizer;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * @license GPL 2+
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ChangeOpValidationExceptionLocalizer implements ExceptionLocalizer {

	/**
	 * @var ValidatorErrorLocalizer
	 */
	protected $validatorErrorLocalizer;

	/**
	 * @param ValueFormatter $paramFormatter A formatter for formatting message parameters
	 *        as wikitext. Typically some kind of dispatcher.
	 */
	public function __construct( ValueFormatter $paramFormatter ) {
		$this->validatorErrorLocalizer = new ValidatorErrorLocalizer( $paramFormatter );
	}

	/**
	 * @see ExceptionLocalizer::getExceptionMessage()
	 *
	 * @param Exception $exception
	 *
	 * @return Message
	 */
	public function getExceptionMessage( Exception $exception ) {
		if ( !$exception instanceof ChangeOpValidationException ) {
			throw new InvalidArgumentException( '$exception is not a ChangeOpValidationException.' );
		}

		$result = $exception->getValidationResult();

		foreach ( $result->getErrors() as $error ) {
			$msg = $this->validatorErrorLocalizer->getErrorMessage( $error );
			return $msg;
		}

		return wfMessage( 'wikibase-validator-invalid' );
	}

	/**
	 * @see ExceptionLocalizer::getExceptionMessage()
	 *
	 * @param Exception $exception
	 *
	 * @return bool
	 */
	public function hasExceptionMessage( Exception $exception ) {
		return $exception instanceof ChangeOpValidationException;
	}
}
