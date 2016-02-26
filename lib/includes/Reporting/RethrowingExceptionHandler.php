<?php

namespace Wikibase\Lib\Reporting;

use Exception;

/**
 * RethrowingExceptionHandler handles exceptions by re-throwing them.
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class RethrowingExceptionHandler implements ExceptionHandler {

	/**
	 * Rethrows the given exception;
	 *
	 * @see ExceptionHandler::handleException()
	 *
	 * @param Exception $exception
	 * @param string $errorCode
	 * @param string $explanation
	 *
	 * @throws Exception
	 */
	public function handleException( Exception $exception, $errorCode, $explanation ) {
		throw $exception;
	}

}
