<?php

namespace Wikibase\Repo\Localizer;

use Exception;
use InvalidArgumentException;
use Message;
use ValueFormatters\ValueFormatter;
use Wikibase\Repo\ChangeOp\ChangeOpValidationException;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ChangeOpValidationExceptionLocalizer implements ExceptionLocalizer {

	/**
	 * @var ValidatorErrorLocalizer
	 */
	private $validatorErrorLocalizer;

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
	 * @throws InvalidArgumentException
	 * @return Message
	 */
	public function getExceptionMessage( Exception $exception ) {
		if ( !$this->hasExceptionMessage( $exception ) ) {
			throw new InvalidArgumentException( '$exception is not a ChangeOpValidationException.' );
		}

		/** @var ChangeOpValidationException $exception */
		'@phan-var ChangeOpValidationException $exception';
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
