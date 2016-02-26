<?php

namespace Wikibase\Repo\Localizer;

use Exception;
use InvalidArgumentException;
use Message;
use Wikibase\Lib\MessageException;

/**
 * @license GPL-2.0+
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
	 * @throws InvalidArgumentException
	 */
	public function getExceptionMessage( Exception $exception ) {
		if ( !$this->hasExceptionMessage( $exception ) ) {
			throw new InvalidArgumentException( '$exception is not a MessageException.' );
		}

		/** @var MessageException $exception */
		return new Message( $exception->getKey(), $exception->getParams() );
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
