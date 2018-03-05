<?php

namespace Wikibase\Lib\Reporting;

use Exception;

/**
 * ReportingExceptionHandler reports exceptions to a MessageReporter.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ReportingExceptionHandler implements ExceptionHandler {

	/**
	 * @var MessageReporter
	 */
	protected $reporter;

	public function __construct( MessageReporter $reporter ) {
		$this->reporter = $reporter;
	}

	/**
	 * Reports the exception to the MessageReporter defined in the constructor call.
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
		$this->reporter->reportMessage( $msg );
	}

}
