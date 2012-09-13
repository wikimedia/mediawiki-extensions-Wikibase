<?php

namespace Wikibase;

/**
 * Represents a single Wikibase datatype.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TypeObject extends EntityObject implements Query {

	/**
	 * @see EntityObject::getIdPrefix()
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	protected function getIdPrefix() {
		return 't'; // TODO: decide on what to use here
	}

	/**
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return Type
	 */
	public static function newFromArray( array $data ) {
		return new static( $data );
	}

	/**
	 * @since 0.1
	 *
	 * @return Type
	 */
	public static function newEmpty() {
		return self::newFromArray( array() );
	}

	/**
	 * @see Entity::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return Type::ENTITY_TYPE;
	}

	/**
	 * @see Entity::getDiff
	 *
	 * @since 0.1
	 *
	 * @param Entity $target
	 *
	 * @return TypeDiff
	 */
	public function getDiff( Entity $target ) {
		// TODO
		return ItemDiff::newEmpty();
	}

}
