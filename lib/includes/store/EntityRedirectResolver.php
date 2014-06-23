<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use Wikibase\EntityId;

/**
 * Utility class for recursively resolving entity redirects by repeatedly calling
 * a callback. The callback is expected to throw an UnresolvedRedirectException
 * if it encounters a redirect.
 *
 * This provides a wrapper around a target object, adding "retry" logic to any method
 * call that has en EntityId as its first parameter.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityRedirectResolver {

	/**
	 * @var object
	 */
	private $target;

	/**
	 * @var int
	 */
	private $levels;

	/**
	 * @param object $target The object this resolve should forward method calls to.
	 * @param int $levels The number of redirect levels to resolve.
	 *
	 * @throws \InvalidArgumentException If $callback is not callable.
	 */
	public function __construct( $target, $levels = 1 ) {
		if ( !is_object( $target ) ) {
			throw new InvalidArgumentException( '$target must be an object' );
		}

		if ( !is_int( $levels) || $levels < 0 ) {
			throw new InvalidArgumentException( '$level must be a positive integer' );
		}

		$this->target = $target;
		$this->levels = $levels;
	}

	/**
	 * Magic method handling the invocation of any undefined methods on this EntityRedirectResolver,
	 * forwarding it to the object specified in the constructor.
	 *
	 * If calling the method on the target object throws an UnresolvedRedirectException, and the
	 * first $arguments[0] is an EntityId, the call is retried using the target EntityId supplied
	 * by the UnresolvedRedirectException.
	 *
	 * This essentially adds transparent redirect resolution to the respective methods of the
	 * target object.
	 *
	 */
	function __call( $name, $arguments ) {
		$method = array( $this->target, $name );
		$ex = null;

		// NOTE: level = 1 means "try twice"!
		for ( $i = 0; $i <= $this->levels; $i++ ) {
			try {
				return call_user_func_array( $method, $arguments );
			} catch ( UnresolvedRedirectException $ex ) {
				if ( isset( $arguments[0] ) && ( $arguments[0] instanceof EntityId ) ) {
					$arguments[0] = $ex->getRedirectTargetId();
				} else {
					throw $ex;
				}
			}
		}

		// We know that $ex is not null, because:
		// $this->level is at least 0, so the loop is entered at least once.
		// If no exception occurs, the loop body terminates the function.
		// If an uncaught exception is thrown, it will also exit the function.
		// Otherwise, $ex will be defined.
		throw $ex;
	}

}
