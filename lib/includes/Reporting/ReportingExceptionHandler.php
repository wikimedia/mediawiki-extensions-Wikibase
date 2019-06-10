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
	 * Exception classes that should be ignored.
	 * @var string[]
	 */
	private $ignored;

	public function __construct( MessageReporter $reporter, array $ignored = [] ) {
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
		foreach ( $this->ignored as $ignored ) {
			if ( $exception instanceof $ignored ) {
				return;
			}
		}

		$msg = $exception->getMessage();

		$msg = '[' . $errorCode . ']: ' . $explanation . ' (' . $msg . ')';
		$this->reporter->reportMessage( $msg );
	}

}
