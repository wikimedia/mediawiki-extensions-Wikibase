<?php

namespace Wikibase\Lib\Reporting;

use Exception;
use Onoi\MessageReporter\MessageReporter;

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
	/**
	 * Message types that would be ignored.
	 * @var string[]
	 */
	private $ignored;

	public function __construct( MessageReporter $reporter, $ignored = [] ) {
		$this->reporter = $reporter;
		$this->ignored = $ignored;
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
		if ( in_array( $errorCode, $this->ignored ) ) {
			return;
		}
		$msg = $exception->getMessage();

		$msg = '[' . $errorCode . ']: ' . $explanation . ' (' . $msg . ')';
		$this->reporter->reportMessage( $msg );
	}

}
