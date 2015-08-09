<?php

namespace Wikibase\DataModel\Services\Entity;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Generic decorator class for transparently resolving EntityRedirects.
 *
 * This is done by delegating calls to a target object while adding a kind of
 * "retry" logic to any method that has an EntityId as its first parameter and
 * throws an UnresolvedRedirectException when that EntityId refers to a redirect.
 *
 * This effectively adds transparent redirect resolution to all methods of the
 * target object.
 *
 * @since 1.1
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityRedirectResolvingDecorator {

	/**
	 * @var object
	 */
	private $targetObject;

	/**
	 * @var int The maximum number of redirects to follow
	 */
	private $maxResolutionDepth;

	/**
	 * Constructs a decorator for the given target object. The resulting proxy
	 * object supports all methods of the target object, but does not formally implement
	 * any interface.
	 *
	 * The decorator is effective for any method that takes an EntityId as its first parameter,
	 * and throws an UnresolvedRedirectException when that EntityId refers to a redirect.
	 *
	 * @param object $targetObject The object to attach the redirect resolution decorator to.
	 *        Typically an EntityLookup or EntityRevisionLookup.
	 *
	 * @param int $maxResolutionDepth The maximum number of redirect levels to resolve
	 *        on each function call.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $targetObject, $maxResolutionDepth = 1 ) {
		if ( !is_object( $targetObject ) ) {
			throw new InvalidArgumentException( '$target must be an object' );
		}

		if ( !is_int( $maxResolutionDepth ) || $maxResolutionDepth < 0 ) {
			throw new InvalidArgumentException( '$maxResolutionDepth must be a positive integer' );
		}

		$this->targetObject = $targetObject;
		$this->maxResolutionDepth = $maxResolutionDepth;
	}

	/**
	 * Method invocation handler which delegates calls to the target object supplied to
	 * the constructor. This adds a kind of "retry" logic to any method that has an
	 * EntityId as its first parameter and throws an UnresolvedRedirectException when a
	 * redirect is encountered.
	 *
	 * This essentially adds transparent redirect resolution to the respective methods of the
	 * target object.
	 */
	public function __call( $name, $arguments ) {
		$retries = $this->maxResolutionDepth;

		do {
			try {
				return call_user_func_array( array( $this->targetObject, $name ), $arguments );
			} catch ( UnresolvedRedirectException $ex ) {
				// If the first argument was an EntityId, replace it and retry.
				// Otherwise, give up.
				if ( !isset( $arguments[0] ) || !( $arguments[0] instanceof EntityId ) ) {
					break;
				}

				$arguments[0] = $ex->getRedirectTargetId();
			}
		} while ( $retries-- );

		throw $ex;
	}

}
