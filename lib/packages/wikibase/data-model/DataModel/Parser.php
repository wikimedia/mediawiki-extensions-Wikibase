<?php

namespace Wikibase;

/**
 * Interface for objects that can parse
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
interface Parser {

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