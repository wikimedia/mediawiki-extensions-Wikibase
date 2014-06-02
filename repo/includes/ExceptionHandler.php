<?php

/**
 * Interface for objects that can handle exceptions.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface ExceptionHandler {

	/**
	 * Handle the given exception. Typical ways to handle an exception are to
	 * re-throw it, ignore it or log it.
	 *
	 * @since 0,5
	 *
	 * @param Exception $exception
	 * @param $errorCode
	 * @param $explanation
	 */
	public function handleException( Exception $exception, $errorCode, $explanation );

}

/**
 * ReportingExceptionHandler reports exceptions to a MessageReporter.
 *
 * @license GPL 2+
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
	 * @param $errorCode
	 * @param $explanation
	 */
	public function handleException( Exception $exception, $errorCode, $explanation ) {
		$msg = $exception->getMessage();

		$msg =  '[' . $errorCode . ']: ' . $explanation . ' (' . $msg . ')';
		$this->reporter->reportMessage( $msg );
	}
}

/**
 * RethrowingExceptionHandler handles exceptions by re-throwing them.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class RethrowingExceptionHandler implements ExceptionHandler {

	/**
	 * Rethrows the given exception;
	 *
	 * @see ExceptionHandler::handleException()
	 *
	 * @param Exception $exception
	 * @param $errorCode
	 * @param $explanation
	 *
	 * @throws Exception
	 */
	public function handleException( Exception $exception, $errorCode, $explanation ) {
		throw $exception;
	}
}
