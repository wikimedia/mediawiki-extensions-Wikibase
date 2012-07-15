<?php

namespace Wikibase;

/**
 * Represents a single Wikibase query.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class QueryObject extends EntityObject implements Query {

	/**
	 * @see EntityObject::getIdPrefix()
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	protected function getIdPrefix() {
		return 'query'; // TODO: decide on what to use here
	}

	/**
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return Query
	 */
	public static function newFromArray( array $data ) {
		return new static( $data );
	}

	/**
	 * @since 0.1
	 *
	 * @return Query
	 */
	public static function newEmpty() {
		return self::newFromArray( array() );
	}

}
