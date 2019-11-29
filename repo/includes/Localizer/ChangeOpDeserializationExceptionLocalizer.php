<?php

namespace Wikibase\Repo\Localizer;

use Exception;
use InvalidArgumentException;
use Message;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;

/**
 * Localizes ChangeOpDeserializationExceptions.
 * NOTE: Only exceptions with error codes that prepended with "wikibase-api" form the i18n message key are localized.
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpDeserializationExceptionLocalizer implements ExceptionLocalizer {

	/**
	 * @see ExceptionLocalizer::hasExceptionMessage()
	 *
	 * @param Exception $exception
	 *
	 * @return bool
	 */
	public function hasExceptionMessage( Exception $exception ) {
		if ( !$exception instanceof ChangeOpDeserializationException ) {
			return false;
		}

		$message = new Message( 'wikibase-api-' . $exception->getErrorCode() );

		return $message->exists();
	}

	/**
	 * @see ExceptionLocalizer::getExceptionMessage()
	 *
	 * @param Exception $exception
	 *
	 * @return Message
	 * @throws InvalidArgumentException
	 */
	public function getExceptionMessage( Exception $exception ) {
		if ( !$this->hasExceptionMessage( $exception ) ) {
			throw new InvalidArgumentException( '$exception cannot be localized.' );
		}

		/** @var ChangeOpDeserializationException $exception */
		'@phan-var ChangeOpDeserializationException $exception';
		return new Message( 'wikibase-api-' . $exception->getErrorCode(), $exception->getParams() );
	}

}
