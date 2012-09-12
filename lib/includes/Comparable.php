<?php

namespace Wikibase;

/**
 * Interface for objects with an equals method.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Comparable {

	/**
	 * Returns if the provided variable is the same as $this, value wise.
	 *
	 * @since 0.1
	 *
	 * @param mixed $mixed
	 *
	 * @return boolean
	 */
	public function equals( $mixed );

}
