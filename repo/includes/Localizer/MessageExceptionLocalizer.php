<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Localizer;

use Exception;
use InvalidArgumentException;
use Message;
use Wikibase\Lib\MessageException;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class MessageExceptionLocalizer implements ExceptionLocalizer {

	public function getExceptionMessage( Exception $exception ): Message {
		if ( !$this->hasExceptionMessage( $exception ) ) {
			throw new InvalidArgumentException( '$exception is not a MessageException.' );
		}

		/** @var MessageException $exception */
		'@phan-var MessageException $exception';
		return new Message( $exception->getKey(), $exception->getParams() );
	}

	public function hasExceptionMessage( Exception $exception ): bool {
		return $exception instanceof MessageException;
	}

}
