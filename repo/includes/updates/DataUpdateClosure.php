<?php

namespace Wikibase\Updates;

use DataUpdate;
use Exception;
use InvalidArgumentException;
use Wikibase\Lib\Reporting\ExceptionHandler;
use Wikibase\Lib\Reporting\LogWarningExceptionHandler;

/**
 * A generic DataUpdate based on a callable passed to the constructor.
 * Together with any additional parameters provided to the constructor an
 * instance of this methods constitutes a closure for a call to the callable.
 *
 * @since 0.5
 *
 * @todo Propose for MediaWiki core.
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DataUpdateClosure extends DataUpdate {

	/**
	 * @var callable
	 */
	private $function;

	/**
	 * @var array
	 */
	private $arguments;

	/**
	 * @var ExceptionHandler
	 */
	private $exceptionHandler;

	/**
	 * @param callable $function
	 * @param mixed [$args,...]
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $function ) {
		if ( !is_callable( $function ) ) {
			throw new InvalidArgumentException( '$function must be callable' );
		}

		$args = func_get_args();
		array_shift( $args );

		$this->function = $function;
		$this->arguments = $args;

		$this->exceptionHandler = new LogWarningExceptionHandler();
	}

	public function setExceptionHandler( ExceptionHandler $exceptionHandler ) {
		$this->exceptionHandler = $exceptionHandler;
	}

	/**
	 * Calls the function specified in the constructor with any additional arguments
	 * passed to the constructor.
	 */
	public function doUpdate() {
		try {
			call_user_func_array( $this->function, $this->arguments );
		} catch ( Exception $ex ) {
			$this->exceptionHandler->handleException( $ex, 'data-update-failed', 'A data update callback triggered an exception' );
		}
	}

}
