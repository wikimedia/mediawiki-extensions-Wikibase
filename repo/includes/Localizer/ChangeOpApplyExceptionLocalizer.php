<?php

declare( strict_types = 1 );

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

	public function hasExceptionMessage( Exception $exception ): bool {
		if ( !$exception instanceof ChangeOpApplyException ) {
			return false;
		}

		return $this->getMessage( $exception )->exists();
	}

	public function getExceptionMessage( Exception $exception ): Message {
		if ( !$this->hasExceptionMessage( $exception ) ) {
			throw new InvalidArgumentException( '$exception cannot be localized.' );
		}

		/** @var ChangeOpApplyException $exception */
		'@phan-var ChangeOpApplyException $exception';
		return $this->getMessage( $exception );
	}

	private function getMessage( ChangeOpApplyException $exception ): Message {
		return new Message( $exception->getMessage(), $exception->getParams() );
	}

}
