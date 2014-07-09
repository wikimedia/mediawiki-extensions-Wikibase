<?php

namespace Wikibase\Lib\Reporting;

use Exception;

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
	 * @since 0.5
	 *
	 * @param Exception $exception
	 * @param string $errorCode
	 * @param string $explanation
	 */
	public function handleException( Exception $exception, $errorCode, $explanation );

}
