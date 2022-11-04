<?php

namespace Wikibase\Lib\Reporting;

use Exception;

/**
 * LogWarningExceptionHandler logs exceptions via wfLogWarning.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class LogWarningExceptionHandler implements ExceptionHandler {

	/**
	 * Reports the exception to wfLogWarning.
	 *
	 * @see ExceptionHandler::handleException()
	 *
	 * @param Exception $exception
	 * @param string $errorCode
	 * @param string $explanation
	 */
	public function handleException( Exception $exception, $errorCode, $explanation ) {
		$class = get_class( $exception );
		$file = $exception->getFile();
		$line = $exception->getLine();
		$msg = $exception->getMessage();
		$msg = "[$errorCode]: $explanation ($class at $file:$line: $msg)";

		wfLogWarning( $msg, 2 );
	}

}
