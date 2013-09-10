<?php

namespace Wikibase\DataModel\Entity;

/**
 * Interface for objects that can parse EntityIds
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
interface EntityIdParser {

	/**
	 * Parses the given param
	 *
	 * @since 0.5
	 *
	 * @param mixed $data
	 *
	 * @return mixed
	 */
	public function parse( $data );

}