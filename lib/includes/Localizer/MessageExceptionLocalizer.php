<?php

namespace Wikibase\Lib\Localizer;

use Exception;
use InvalidArgumentException;
use Message;
use MessageException;

/**
 * @license GPL 2+
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class MessageExceptionLocalizer implements ExceptionLocalizer {

	/**
	 * @see ExceptionLocalizer::getExceptionMessage()
	 *
	 * @param Exception $exception
	 *
	 * @return Message
	 */
	public function getExceptionMessage( Exception $exception ) {
		if ( !$exception instanceof MessageException ) {
			throw new InvalidArgumentException( '$exception is not a MessageException.' );
		}

		$key = $exception->getKey();
		$params = $exception->getParams();
		$msg = wfMessage( $key )->params( $params );

		return $msg;
	}

	/**
	 * @see ExceptionLocalizer::hasExceptionMessage()
	 *
	 * @param Exception $exception
	 *
	 * @return bool
	 */
	public function hasExceptionMessage( Exception $exception ) {
		return $exception instanceof MessageException;
	}
}
