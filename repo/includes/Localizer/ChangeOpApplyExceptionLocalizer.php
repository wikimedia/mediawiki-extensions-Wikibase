<?php

namespace Wikibase\Repo\Localizer;

use Exception;
use InvalidArgumentException;
use Message;
use Wikibase\Repo\ChangeOp\ChangeOpApplyException;

/**
 * Localizes ChangeOpApplyExceptions.
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpApplyExceptionLocalizer implements ExceptionLocalizer {

	/**
	 * @see ExceptionLocalizer::hasExceptionMessage()
	 *
	 * @param Exception $exception
	 *
	 * @return bool
	 */
	public function hasExceptionMessage( Exception $exception ) {
		if ( !$exception instanceof ChangeOpApplyException ) {
			return false;
		}

		return $this->getMessage( $exception )->exists();
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

		/** @var ChangeOpApplyException $exception */
		'@phan-var ChangeOpApplyException $exception';
		return $this->getMessage( $exception );
	}

	/**
	 * @param ChangeOpApplyException $exception
	 * @return Message
	 */
	private function getMessage( ChangeOpApplyException $exception ) {
		return new Message( $exception->getMessage(), $exception->getParams() );
	}

}
