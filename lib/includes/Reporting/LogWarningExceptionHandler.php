<?php

namespace Wikibase\Lib\Reporting;

use Exception;

/**
 * LogWarningExceptionHandler logs exceptions via wfLogWarning.
 *
 * @since 0.5
 *
 * @license GPL 2+
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
		$msg = $exception->getMessage();
		$msg = '[' . $errorCode . ']: ' . $explanation . ' (' . $msg . ')';

		wfLogWarning( $msg, 2 );
	}

}
