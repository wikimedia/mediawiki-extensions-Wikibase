<?php

namespace Wikibase\Repo\Content;

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
 * @todo Propose for MediaWiki core.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class DataUpdateAdapter extends DataUpdate {

	/**
	 * @var callable
	 */
	private $doUpdateFunction;

	/**
	 * @var array
	 */
	private $arguments;

	/**
	 * @var ExceptionHandler
	 */
	private $exceptionHandler;

	/**
	 * @param callable $doUpdateFunction
	 * @param mixed [$args,...]
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( callable $doUpdateFunction /*...*/ ) {
		$args = func_get_args();
		array_shift( $args );

		$this->doUpdateFunction = $doUpdateFunction;
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
			call_user_func_array( $this->doUpdateFunction, $this->arguments );
		} catch ( Exception $ex ) {
			$this->exceptionHandler->handleException( $ex, 'data-update-failed',
				'A data update callback triggered an exception' );
		}
	}

}
