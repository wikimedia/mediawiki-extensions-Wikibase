<?php

namespace Wikibase;

/**
 * Contains methods to generate and obtain an unique id.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface IdGenerator {

	/**
	 * @since 0.1
	 *
	 * @todo: Change this to return an EntityId
	 *
	 * @param string $type
	 *
	 * @return int
	 */
	public function getNewId( $type );

}