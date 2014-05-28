<?php

namespace Wikibase\Updates;

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
class DataUpdateClosure extends \DataUpdate {

	/**
	 * @var callable
	 */
	protected $function;

	/**
	 * @var array
	 */
	protected $arguments;

	/**
	 * @param callable $function
	 * @param mixed [$arg, ...]
	 */
	public function __construct( $function ) {
		if ( !is_callable( $function ) ) {
			throw new \InvalidArgumentException( '$function must be callable' );
		}

		$args = func_get_args();
		array_shift( $args );

		$this->function = $function;
		$this->arguments = $args;
	}

	/**
	 * Calls the function specified in the constructor with any additional arguments
	 * passed to the constructor.
	 *
	 * @since 0.5
	 */
	public function doUpdate() {
		call_user_func_array( $this->function, $this->arguments );
	}

}
