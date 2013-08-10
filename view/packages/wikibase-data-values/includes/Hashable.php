<?php

/**
 * Interface for objects that have a getHash method.
 *
 * @since 0.1
 *
 * @file
 * @ingroup DataValue
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Hashable {

	/**
	 * Returns a hash based on the value of the object.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHash();

}