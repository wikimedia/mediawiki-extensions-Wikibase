<?php

namespace Wikibase;

/**
 * Interface for object to which one can subscribe to receive changes.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Subscribable {

	/**
	 * Subscribes the provided function to changes.
	 *
	 * @since 0.1
	 *
	 * @param callable $function
	 */
	public function subscribe( /* callable */ $function );

	/**
	 * Unsubscribes the provided function from changes.
	 *
	 * @since 0.1
	 *
	 * @param callable $function
	 */
	public function unsubscribe( /* callable */ $function );

}
