<?php

declare( strict_types = 1 );

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

	private ValidatorErrorLocalizer $validatorErrorLocalizer;

	/**
	 * @param ValueFormatter $paramFormatter A formatter for formatting message parameters
	 *        as wikitext. Typically some kind of dispatcher.
	 */
	public function __construct( ValueFormatter $paramFormatter ) {
		$this->validatorErrorLocalizer = new ValidatorErrorLocalizer( $paramFormatter );
	}

	public function getExceptionMessage( Exception $exception ): Message {
		if ( !( $exception instanceof ChangeOpValidationException ) ) {
			throw new InvalidArgumentException( '$exception is not a ChangeOpValidationException.' );
		}

		$result = $exception->getValidationResult();

		foreach ( $result->getErrors() as $error ) {
			$msg = $this->validatorErrorLocalizer->getErrorMessage( $error );
			return $msg;
		}

		return wfMessage( 'wikibase-validator-invalid' );
	}

	public function hasExceptionMessage( Exception $exception ): bool {
		return $exception instanceof ChangeOpValidationException;
	}

}
