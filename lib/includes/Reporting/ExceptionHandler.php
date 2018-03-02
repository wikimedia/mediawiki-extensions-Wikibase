<?php

namespace Wikibase\Lib\Reporting;

use Exception;

/**
 * Interface for objects that can handle exceptions.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
interface ExceptionHandler {

	/**
	 * Handle the given exception. Typical ways to handle an exception are to
	 * re-throw it, ignore it or log it.
	 *
	 * @param Exception $exception
	 * @param string $errorCode
	 * @param string $explanation
	 */
	public function handleException( Exception $exception, $errorCode, $explanation );

}
