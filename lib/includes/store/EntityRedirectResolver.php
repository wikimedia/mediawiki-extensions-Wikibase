<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use Wikibase\EntityId;

/**
 * Utility class for recursively resolving entity redirects by repeatedly calling
 * a callback. The callback is expected to throw an UnresolvedRedirectException
 * if it encounters a redirect.
 *
 * This provides a wrapper around a function call, turning a call that fails on
 * redirects into one that resolves redirects automatically.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityRedirectResolver {

	/**
	 * @var callable
	 */
	private $callback;

	/**
	 * @param callable $callback A callable that takes an EntityId as its first and
	 * only required parameter. The callable should signal redirects using an
	 * UnresolvedRedirectException.
	 *
	 * @throws InvalidArgumentException If $callback is not callable.
	 */
	public function __construct( $callback ) {
		if ( !is_callable( $callback ) ) {
			throw new InvalidArgumentException( '$callback must be callable' );
		}

		$this->callback = $callback;
	}

	/**
	 * Looks up some value for the given EntityId using the callback provided to the
	 * constructor, resolving at most $levels redirects encountered during that process.
	 *
	 * @param EntityId $id The ID to look up
	 * @param int $levels The maximum number of redirect levels to resolve
	 *
	 * @return mixed The return value of the callback provided to the constructor.
	 *
	 * @throws UnresolvedRedirectException If more that $level redirects are encountered.
	 */
	public function apply( EntityId $id, $levels = 1 ) {
		try {
			return call_user_func( $this->callback, $id );
		} catch ( UnresolvedRedirectException $ex ) {
			if ( $levels > 0 ) {
				$target = $ex->getRedirectTargetId();
				return $this->apply( $target, $levels - 1 );
			} else {
				throw $ex;
			}
		}
	}

}
