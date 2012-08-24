<?php

namespace Wikibase;

/**
 * Contains methods to generate and obtain an unique id.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface IdGenerator {

	/**
	 *
	 *
	 * @since 0.1
	 *
	 * @param string $type
	 *
	 * @return integer
	 */
	public function getNewId( $type );

}