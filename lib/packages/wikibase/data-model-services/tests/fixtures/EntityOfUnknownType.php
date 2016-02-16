<?php

namespace Wikibase\DataModel\Services\Fixtures;

use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityOfUnknownType implements EntityDocument {

	/**
	 * @return null
	 */
	public function getId() {
		return null;
	}

	public function getType() {
		return 'unknown-entity-type';
	}

	/**
	 * @param mixed $id Ignored.
	 */
	public function setId( $id ) {
	}

	/**
	 * @return bool Always true.
	 */
	public function isEmpty() {
		return true;
	}

	/**
	 * @see EntityDocument::equals
	 *
	 * @since 3.3
	 *
	 * @param mixed $target
	 *
	 * @return bool Always true.
	 */
	public function equals( $target ) {
		return true;
	}

	/**
	 * @see EntityDocument::copy
	 *
	 * @since 3.3
	 *
	 * @return self
	 */
	public function copy() {
		return $this;
	}

}
