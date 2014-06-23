<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Generic wrapper class for transparently resolving EntityRedirects.
 *
 * This is done by delegating calls to a target object while adding "retry" logic to
 * any method that has an EntityId as its first parameter and throws an
 * UnresolvedRedirectException when a redirect is encountered.
 *
 * This effectively adds transparent redirect resolution to the respective methods of the
 * target object.
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
	 * @var int The maximum number of redirects to follow
	 */
	private $maxResolutionDepth;

	/**
	 * @param object $target The object this resolve should forward method calls to.
	 *        Typically an EntityLookup or EntityRevisionLookup.
	 * @param int $maxResolutionDepth The maximum number of redirect levels to resolve
	 *        on each function call.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $target, $maxResolutionDepth = 1 ) {
		if ( !is_object( $target ) ) {
			throw new InvalidArgumentException( '$target must be an object' );
		}

		if ( !is_int( $maxResolutionDepth) || $maxResolutionDepth < 0 ) {
			throw new InvalidArgumentException( '$level must be a positive integer' );
		}

		$this->target = $target;
		$this->maxResolutionDepth = $maxResolutionDepth;
	}

	/**
	 * Method invocation handler which delegates calls to the target object supplied to
	 * the constructor. This adds "retry" logic to any method that has an EntityId as
	 * its first parameter and throws an UnresolvedRedirectException when a redirect is
	 * encountered.
	 *
	 * This essentially adds transparent redirect resolution to the respective methods of the
	 * target object.
	 */
	public function __call( $name, $arguments ) {
		$method = array( $this->target, $name );
		$ex = null;

		// NOTE: maxResolutionDepth = 1 means "try twice"!
		for ( $i = 0; $i <= $this->maxResolutionDepth; $i++ ) {
			try {
				return call_user_func_array( $method, $arguments );
			} catch ( UnresolvedRedirectException $ex ) {
				// If the first argument was an EntityId, replace it and retry.
				// Otherwise, give up.
				if ( !isset( $arguments[0] ) || !( $arguments[0] instanceof EntityId ) ) {
					break;
				}

				$arguments[0] = $ex->getRedirectTargetId();
			}
		}

		// $ex can't be null here, because the above loop is executed at least once.
		// That means it will either exit from the method, or catch exception $ex.
		throw $ex;
	}

}
